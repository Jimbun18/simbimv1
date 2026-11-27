import { Capacitor } from '@capacitor/core'
import { LocalNotifications } from '@capacitor/local-notifications'
import api from './api'

const CHECKIN_NOTIF_ID = 1001
const CHECKOUT_NOTIF_ID = 1002

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  const outputArray = new Uint8Array(rawData.length)
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i)
  }
  return outputArray
}

export function isWebPushSupported(): boolean {
  return (
    !Capacitor.isNativePlatform() &&
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window
  )
}

export function getNotificationPermissionState(): NotificationPermission | 'unsupported' | 'native' {
  if (Capacitor.isNativePlatform()) return 'native'
  if (!isWebPushSupported()) return 'unsupported'
  return Notification.permission
}

export async function subscribeWebPush(): Promise<{ ok: boolean; message: string }> {
  if (Capacitor.isNativePlatform()) {
    return syncCapacitorLocalReminders()
  }

  if (!isWebPushSupported()) {
    return { ok: false, message: 'Browser ini belum mendukung Web Push Notification.' }
  }

  const permission = await Notification.requestPermission()
  if (permission !== 'granted') {
    return { ok: false, message: 'Izin notifikasi browser ditolak.' }
  }

  try {
    const keyRes = await api.get('/push/vapid-public-key')
    const publicKey = keyRes.data?.data?.public_key as string | null
    if (!publicKey) {
      return { ok: false, message: 'VAPID public key belum dikonfigurasi di server.' }
    }

    const registration = await navigator.serviceWorker.ready
    let subscription = await registration.pushManager.getSubscription()
    if (!subscription) {
      const applicationServerKey = urlBase64ToUint8Array(publicKey)
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey.buffer as ArrayBuffer,
      })
    }

    const json = subscription.toJSON()
    if (!json.endpoint || !json.keys?.p256dh || !json.keys?.auth) {
      return { ok: false, message: 'Gagal membaca kunci langganan push dari browser.' }
    }

    await api.post('/push/subscribe', {
      endpoint: json.endpoint,
      keys: {
        p256dh: json.keys.p256dh,
        auth: json.keys.auth,
      },
      content_encoding: 'aesgcm',
    })

    return { ok: true, message: 'Notifikasi perangkat berhasil diaktifkan.' }
  } catch (err: any) {
    return {
      ok: false,
      message: err?.response?.data?.message || err?.message || 'Gagal mengaktifkan notifikasi push.',
    }
  }
}

export async function sendTestPush(): Promise<{ ok: boolean; message: string }> {
  if (Capacitor.isNativePlatform()) {
    try {
      const perm = await LocalNotifications.requestPermissions()
      if (perm.display !== 'granted') {
        return { ok: false, message: 'Izin notifikasi Android ditolak.' }
      }
      await LocalNotifications.schedule({
        notifications: [
          {
            id: 9999,
            title: 'Uji Notifikasi SIM-BIM',
            body: 'Pengingat presensi rutin di Android siap digunakan.',
            schedule: { at: new Date(Date.now() + 1000) },
          },
        ],
      })
      return { ok: true, message: 'Notifikasi uji coba dijadwalkan di perangkat Android.' }
    } catch (err: any) {
      return { ok: false, message: err?.message || 'Gagal mengirim notifikasi uji coba Android.' }
    }
  }

  try {
    const res = await api.post('/push/test')
    return { ok: !!res.data?.success, message: res.data?.message || 'Uji notifikasi selesai.' }
  } catch (err: any) {
    return {
      ok: false,
      message: err?.response?.data?.message || 'Gagal mengirim uji notifikasi.',
    }
  }
}

/**
 * Menjadwalkan pengingat lokal harian pada aplikasi Android (Capacitor)
 * sesuai pengaturan jam check-in & check-out dari Admin.
 */
export async function syncCapacitorLocalReminders(): Promise<{ ok: boolean; message: string }> {
  if (!Capacitor.isNativePlatform()) {
    return { ok: true, message: 'Bukan platform native.' }
  }

  try {
    const perm = await LocalNotifications.requestPermissions()
    if (perm.display !== 'granted') {
      return { ok: false, message: 'Izin notifikasi Android belum diberikan.' }
    }

    const [settingsRes, todayRes] = await Promise.allSettled([
      api.get('/settings'),
      api.get('/attendance/teachers/today'),
    ])

    const rawGroups =
      settingsRes.status === 'fulfilled' ? settingsRes.value.data?.data || {} : {}
    const notifSettings: Record<string, any> = {}
    for (const group of Object.values(rawGroups) as Array<Record<string, any>>) {
      if (group && typeof group === 'object') {
        for (const [k, v] of Object.entries(group)) {
          notifSettings[k] = v && typeof v === 'object' && 'value' in v ? v.value : v
        }
      }
    }
    const todayAttendance =
      todayRes.status === 'fulfilled' ? todayRes.value.data?.data : null

    const checkinEnabled = String(notifSettings['notification.checkin_reminder.enabled'] ?? '1') !== '0'
    const checkinTime = String(notifSettings['notification.checkin_reminder.time'] || '07:15').substring(0, 5)
    const checkoutEnabled = String(notifSettings['notification.checkout_reminder.enabled'] ?? '1') !== '0'
    const checkoutTime = String(notifSettings['notification.checkout_reminder.time'] || '14:35').substring(0, 5)

    // Hapus jadwal lama sebelum menjadwalkan ulang
    await LocalNotifications.cancel({
      notifications: [{ id: CHECKIN_NOTIF_ID }, { id: CHECKOUT_NOTIF_ID }],
    })

    const toSchedule: any[] = []
    const now = new Date()

    const alreadyCheckedIn = !!todayAttendance?.jam_masuk || ['izin', 'sakit', 'cuti', 'dinas'].includes(todayAttendance?.status)
    const alreadyCheckedOut = !!todayAttendance?.jam_keluar

    if (checkinEnabled) {
      const [h, m] = checkinTime.split(':').map(Number)
      const nextRun = new Date()
      nextRun.setHours(h || 7, m || 15, 0, 0)
      if (nextRun <= now || alreadyCheckedIn) {
        nextRun.setDate(nextRun.getDate() + 1)
      }
      toSchedule.push({
        id: CHECKIN_NOTIF_ID,
        title: 'Pengingat Presensi Masuk',
        body: 'Selamat pagi! Jangan lupa melakukan check-in presensi hari ini di SIM-BIM.',
        schedule: { at: nextRun, every: 'day', allowWhileIdle: true },
        extra: { url: '/presensi' },
      })
    }

    if (checkoutEnabled) {
      const [h, m] = checkoutTime.split(':').map(Number)
      const nextRun = new Date()
      nextRun.setHours(h || 14, m || 35, 0, 0)
      if (nextRun <= now || alreadyCheckedOut) {
        nextRun.setDate(nextRun.getDate() + 1)
      }
      toSchedule.push({
        id: CHECKOUT_NOTIF_ID,
        title: 'Pengingat Presensi Pulang',
        body: 'Waktunya pulang! Jangan lupa check-out presensi sebelum meninggalkan sekolah.',
        schedule: { at: nextRun, every: 'day', allowWhileIdle: true },
        extra: { url: '/presensi' },
      })
    }

    if (toSchedule.length > 0) {
      await LocalNotifications.schedule({ notifications: toSchedule })
    }

    return { ok: true, message: 'Pengingat presensi lokal Android berhasil disinkronkan.' }
  } catch (err: any) {
    return {
      ok: false,
      message: err?.message || 'Gagal menjadwalkan notifikasi lokal Android.',
    }
  }
}
