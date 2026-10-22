<script setup lang="ts">
import { ref } from 'vue'
import type { LeaveForm } from './types'

defineProps<{
  isOpen: boolean
  submitting: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit', formData: FormData): void
}>()

const leaveForm = ref<LeaveForm>({
  status: 'izin',
  tanggal_mulai: new Date().toISOString().split('T')[0],
  tanggal_selesai: new Date().toISOString().split('T')[0],
  keterangan: '',
})
const leaveAttachment = ref<File | null>(null)

const onLeaveFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    leaveAttachment.value = target.files[0]
  }
}

const handleSubmit = () => {
  if (!leaveForm.value.keterangan.trim()) {
    alert('Silakan tuliskan alasan permohonan izin/sakit.')
    return
  }

  const formData = new FormData()
  formData.append('status', leaveForm.value.status)
  formData.append('tanggal_mulai', leaveForm.value.tanggal_mulai)
  formData.append('tanggal_selesai', leaveForm.value.tanggal_selesai)
  formData.append('keterangan', leaveForm.value.keterangan)
  if (leaveAttachment.value) {
    formData.append('lampiran', leaveAttachment.value)
  }

  emit('submit', formData)
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
    @click="emit('close')"
  >
    <div class="max-w-md w-full bg-white rounded-3xl p-6 shadow-2xl space-y-4" @click.stop>
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="font-black text-slate-900 text-base">Pengajuan Izin / Cuti</h3>
          <p class="text-xs text-slate-400">Kirim permohonan ke Kepala Sekolah</p>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-700 text-2xl font-bold p-1 cursor-pointer">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4 text-xs">
        <!-- Jenis Izin -->
        <div>
          <label class="block font-bold text-slate-700 mb-1">Jenis Ketidakhadiran</label>
          <select
            v-model="leaveForm.status"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
          >
            <option value="izin">Izin Kepentingan Pribadi/Keluarga</option>
            <option value="sakit">Sakit</option>
            <option value="dinas">Dinas Luar / Penugasan</option>
          </select>
        </div>

        <!-- Rentang Tanggal -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Mulai Tanggal</label>
            <input
              v-model="leaveForm.tanggal_mulai"
              type="date"
              required
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Sampai Tanggal</label>
            <input
              v-model="leaveForm.tanggal_selesai"
              type="date"
              required
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
          </div>
        </div>

        <!-- Keterangan -->
        <div>
          <label class="block font-bold text-slate-700 mb-1">Alasan / Keterangan</label>
          <textarea
            v-model="leaveForm.keterangan"
            required
            rows="3"
            placeholder="Jelaskan alasan izin / sakit..."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
          ></textarea>
        </div>

        <!-- Lampiran File -->
        <div>
          <label class="block font-bold text-slate-700 mb-1">Lampiran Dokumen / Surat Dokter (Opsional)</label>
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="onLeaveFileChange"
            class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
          />
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            @click="emit('close')"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition disabled:opacity-50 cursor-pointer"
          >
            {{ submitting ? 'Mengirim...' : 'Kirim Pengajuan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
