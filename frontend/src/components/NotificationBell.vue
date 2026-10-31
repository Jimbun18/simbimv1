<template>
  <div class="relative" ref="bellContainer">
    <!-- Tombol Lonceng Notifikasi -->
    <button
      @click="toggleDropdown"
      class="relative p-2 rounded-xl text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 transition-all cursor-pointer active:scale-95"
      title="Notifikasi Sistem"
      aria-label="Lihat Notifikasi"
    >
      <svg class="w-5 h-5 sm:w-5.5 sm:h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>

      <!-- Badge Jumlah Belum Dibaca -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-rose-600 text-white text-[10px] font-black rounded-full flex items-center justify-center ring-2 ring-white shadow-xs animate-pulse"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel Notifikasi -->
    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 sm:-right-4 mt-2 w-[320px] sm:w-[380px] bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 overflow-hidden text-slate-800"
      >
        <!-- Header Panel -->
        <div class="px-4 py-3 bg-gradient-to-r from-emerald-800 to-teal-800 text-white flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-sm">Notifikasi</h3>
            <span
              v-if="unreadCount > 0"
              class="px-2 py-0.5 bg-rose-500/90 text-white text-[10px] font-extrabold rounded-full"
            >
              {{ unreadCount }} Baru
            </span>
          </div>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-[11px] text-emerald-100 hover:text-white underline font-medium cursor-pointer"
          >
            Tandai semua dibaca
          </button>
        </div>

        <!-- Daftar Notifikasi -->
        <div class="max-h-[360px] overflow-y-auto divide-y divide-slate-100">
          <div v-if="loading && notifications.length === 0" class="py-8 text-center text-slate-400 text-xs">
            <svg class="w-6 h-6 animate-spin mx-auto text-emerald-600 mb-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memuat notifikasi...</span>
          </div>

          <div v-else-if="notifications.length === 0" class="py-10 px-4 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-2.5">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </div>
            <p class="text-xs font-bold text-slate-700">Belum Ada Notifikasi</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Pemberitahuan aktivitas SIM-BIM akan muncul di sini.</p>
          </div>

          <div
            v-for="item in notifications"
            :key="item.id"
            @click="handleNotificationClick(item)"
            :class="[
              'p-3.5 flex items-start gap-3 transition-colors cursor-pointer group',
              item.dibaca ? 'bg-white hover:bg-slate-50' : 'bg-emerald-50/40 hover:bg-emerald-50/70'
            ]"
          >
            <!-- Icon Indikator Tipe -->
            <div
              :class="[
                'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 text-white',
                getTypeBadgeClass(item.tipe)
              ]"
            >
              <svg v-if="item.tipe === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else-if="item.tipe === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <svg v-else-if="item.tipe === 'danger'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <!-- Konten Pesan -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-1 mb-0.5">
                <h4
                  :class="[
                    'text-xs truncate font-bold',
                    item.dibaca ? 'text-slate-800' : 'text-emerald-950 font-black'
                  ]"
                >
                  {{ item.judul }}
                </h4>
                <!-- Titik penanda belum dibaca -->
                <span v-if="!item.dibaca" class="w-2 h-2 rounded-full bg-emerald-600 shrink-0"></span>
              </div>
              <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">
                {{ item.isi }}
              </p>
              <div class="flex items-center justify-between mt-1 text-[10px] text-slate-400">
                <span>{{ formatRelativeTime(item.created_at) }}</span>
                <span v-if="item.target_url" class="text-emerald-700 font-semibold group-hover:underline">
                  Buka &rarr;
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Panel -->
        <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[11px]">
          <button
            @click="fetchNotifications"
            class="text-slate-500 hover:text-emerald-700 font-medium cursor-pointer flex items-center gap-1"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Segarkan
          </button>
          <span class="text-slate-400 text-[10px]">SIM-BIM Notifikasi</span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

interface NotificationItem {
  id: number
  judul: string
  isi: string
  tipe: 'info' | 'success' | 'warning' | 'danger'
  target_url?: string | null
  dibaca: boolean
  created_at: string
}

const router = useRouter()
const isOpen = ref(false)
const loading = ref(false)
const unreadCount = ref(0)
const notifications = ref<NotificationItem[]>([])
const bellContainer = ref<HTMLElement | null>(null)

let pollTimer: number | null = null

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    fetchNotifications()
  }
}

const fetchNotifications = async () => {
  try {
    loading.value = true
    const res = await api.get('/notifications')
    if (res.data?.success) {
      notifications.value = res.data.data || []
      unreadCount.value = res.data.unread_count || 0
    }
  } catch (err) {
    // Abaikan jika token/sesi belum siap
  } finally {
    loading.value = false
  }
}

const handleNotificationClick = async (item: NotificationItem) => {
  // Tandai sudah dibaca jika belum
  if (!item.dibaca) {
    try {
      await api.post(`/notifications/${item.id}/read`)
      item.dibaca = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (e) {
      // Abaikan error
    }
  }

  isOpen.value = false

  // Jika memiliki tautan target halaman di SIM-BIM
  if (item.target_url) {
    if (item.target_url.startsWith('http')) {
      window.open(item.target_url, '_blank')
    } else {
      router.push(item.target_url)
    }
  }
}

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all')
    notifications.value.forEach((n) => (n.dibaca = true))
    unreadCount.value = 0
  } catch (e) {
    // Abaikan error
  }
}

const getTypeBadgeClass = (tipe: string) => {
  switch (tipe) {
    case 'success':
      return 'bg-emerald-600'
    case 'warning':
      return 'bg-amber-500'
    case 'danger':
      return 'bg-rose-600'
    default:
      return 'bg-sky-600'
  }
}

const formatRelativeTime = (isoDate: string) => {
  if (!isoDate) return ''
  const diff = Date.now() - new Date(isoDate).getTime()
  const sec = Math.floor(diff / 1000)
  if (sec < 60) return 'Baru saja'
  const min = Math.floor(sec / 60)
  if (min < 60) return `${min} mnt lalu`
  const hour = Math.floor(min / 60)
  if (hour < 24) return `${hour} jam lalu`
  const day = Math.floor(hour / 24)
  if (day === 1) return 'Kemarin'
  if (day < 7) return `${day} hari lalu`
  return new Date(isoDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}

const handleClickOutside = (e: MouseEvent) => {
  if (bellContainer.value && !bellContainer.value.contains(e.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => {
  fetchNotifications()
  document.addEventListener('click', handleClickOutside)
  // Polling data notifikasi setiap 45 detik
  pollTimer = window.setInterval(fetchNotifications, 45000)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (pollTimer) {
    clearInterval(pollTimer)
  }
})
</script>

