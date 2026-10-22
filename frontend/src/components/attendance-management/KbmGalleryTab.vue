<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api from '../../services/api'
import { getPhotoUrl } from './types'

const props = defineProps<{
  classes: any[]
  selectedDate: string
}>()

const emit = defineEmits<{
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void
}>()

const loading = ref(false)
const selectedClassFilter = ref('')
const kbmEvidences = ref<any[]>([])

const selectedEvidenceImage = ref<string | null>(null)
const selectedEvidenceItem = ref<any | null>(null)

const fetchKbmEvidence = async () => {
  loading.value = true
  try {
    const params: any = {
      tanggal: props.selectedDate,
    }
    if (selectedClassFilter.value) {
      params.class_id = selectedClassFilter.value
    }

    const res = await api.get('/attendance/evidence', { params })
    kbmEvidences.value = res.data.data || []
  } catch (err: any) {
    console.error('Failed to fetch KBM evidence:', err)
    emit('notify', 'Gagal memuat galeri bukti KBM', 'error')
  } finally {
    loading.value = false
  }
}

const openEvidenceViewer = (ev: any) => {
  selectedEvidenceItem.value = ev
  selectedEvidenceImage.value = ev.foto_url || getPhotoUrl(ev.photo_path)
}

watch(() => props.selectedDate, () => {
  fetchKbmEvidence()
})

onMounted(() => {
  fetchKbmEvidence()
})

defineExpose({
  fetchKbmEvidence
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
      <div>
        <h3 class="font-bold text-slate-900 text-sm sm:text-base">Galeri Bukti Mengajar & Jurnal KBM Guru</h3>
        <p class="text-xs text-slate-500 mt-0.5">Dokumentasi foto aktivitas kelas, pokok materi ajar, dan koordinat GPS dari sesi Masuk Kelas guru.</p>
      </div>
      <div class="flex items-center gap-2">
        <select
          v-model="selectedClassFilter"
          @change="fetchKbmEvidence"
          class="border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold bg-slate-50 text-slate-800 focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">Semua Rombel</option>
          <option v-for="c in props.classes" :key="c.id" :value="String(c.id)">
            {{ c.nama }}
          </option>
        </select>
        <button 
          @click="fetchKbmEvidence" 
          class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer"
        >
          <span>🔄</span>
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-16 text-slate-400 text-xs">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent mb-2"></div>
      <p>Memuat dokumentasi bukti KBM...</p>
    </div>

    <div v-else-if="kbmEvidences.length === 0" class="text-center py-16 bg-white rounded-3xl border border-dashed border-slate-200 shadow-xs space-y-2">
      <div class="text-3xl">📷</div>
      <h4 class="font-bold text-slate-800 text-sm">Belum Ada Bukti KBM</h4>
      <p class="text-xs text-slate-400 max-w-md mx-auto">
        Belum ada foto bukti mengajar yang diunggah untuk tanggal terpilih. Guru dapat mengunggahnya langsung saat mengklik <strong>"Masuk Kelas"</strong> pada kartu jadwal KBM.
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="ev in kbmEvidences" 
        :key="ev.id"
        class="border border-slate-200/90 rounded-3xl overflow-hidden hover:shadow-xl transition-all duration-300 bg-white flex flex-col justify-between group"
      >
        <div>
          <!-- Photo Thumbnail with Zoom Icon -->
          <div 
            class="h-52 bg-slate-900 relative overflow-hidden group cursor-pointer"
            @click="openEvidenceViewer(ev)"
          >
            <img 
              v-if="ev.foto_url || ev.photo_path" 
              :src="ev.foto_url || getPhotoUrl(ev.photo_path)" 
              alt="Bukti KBM" 
              class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300 opacity-90 group-hover:opacity-100"
            />
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 text-xs gap-1">
              <span class="text-2xl">📷</span>
              <span>Tidak ada foto</span>
            </div>

            <!-- Time Badge -->
            <div class="absolute bottom-2.5 right-2.5 bg-black/70 text-white text-[10px] font-mono font-bold px-2.5 py-1 rounded-lg backdrop-blur-xs flex items-center gap-1">
              <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ ev.captured_at ? new Date(ev.captured_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</span>
            </div>

            <!-- Fullscreen prompt badge -->
            <div class="absolute top-2.5 left-2.5 bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-lg opacity-0 group-hover:opacity-100 transition">
              🔍 Klik untuk perbesar
            </div>
          </div>

          <!-- Content Details -->
          <div class="p-5 space-y-3 text-xs">
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-lg">
                {{ ev.schedule?.school_class?.nama || 'Kelas' }}
              </span>
              <span class="text-[11px] text-purple-700 bg-purple-50 px-2 py-0.5 rounded-lg font-semibold">
                {{ ev.schedule?.subject?.nama || 'Mata Pelajaran' }}
              </span>
            </div>

            <div class="space-y-1">
              <h4 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                <span>👨‍🏫</span>
                <span>{{ ev.teacher?.nama_lengkap || 'Guru Pengampu' }}</span>
              </h4>
              <p class="text-[11px] text-slate-400">
                {{ ev.schedule?.time_slot?.nama || '' }} ({{ ev.schedule?.time_slot?.jam_mulai || '07:30' }} - {{ ev.schedule?.time_slot?.jam_selesai || '09:00' }})
              </p>
            </div>

            <!-- Materi Pokok -->
            <div class="p-3 bg-slate-50 rounded-xl space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pokok Materi Ajar:</span>
              <p class="text-xs text-slate-800 font-semibold leading-relaxed">
                {{ ev.materi_ajar || 'Materi KBM reguler' }}
              </p>
            </div>

            <!-- Catatan KBM -->
            <div v-if="ev.catatan" class="text-slate-600 text-xs italic">
              "{{ ev.catatan }}"
            </div>
          </div>
        </div>

        <!-- Card Footer (GPS status) -->
        <div class="p-4 pt-3 border-t border-slate-100 flex justify-between items-center text-[11px] text-slate-400 font-mono bg-slate-50/50">
          <span>{{ ev.lat ? `${Number(ev.lat).toFixed(4)}, ${Number(ev.lng).toFixed(4)}` : 'Lokasi Sekolah' }}</span>
          <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md flex items-center gap-1">
            <span>📍</span>
            <span>Tervalidasi KBM</span>
          </span>
        </div>
      </div>
    </div>

    <!-- IMAGE MODAL VIEWER (FULLSCREEN BUKTI KBM) -->
    <div 
      v-if="selectedEvidenceImage" 
      class="fixed inset-0 bg-black/85 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="selectedEvidenceImage = null"
    >
      <div class="max-w-3xl w-full bg-slate-900 rounded-3xl overflow-hidden p-3 shadow-2xl space-y-3" @click.stop>
        <div class="p-3 flex items-center justify-between text-white border-b border-slate-800">
          <div>
            <h4 class="text-sm font-bold text-white">
              {{ selectedEvidenceItem?.schedule?.subject?.nama }} — {{ selectedEvidenceItem?.schedule?.school_class?.nama }}
            </h4>
            <p class="text-xs text-slate-400">
              Guru: {{ selectedEvidenceItem?.teacher?.nama_lengkap }} • {{ selectedEvidenceItem?.materi_ajar || 'Materi KBM' }}
            </p>
          </div>
          <button @click="selectedEvidenceImage = null" class="text-white hover:text-slate-300 font-bold text-xl p-1 cursor-pointer">&times;</button>
        </div>

        <div class="flex items-center justify-center max-h-[75vh] overflow-hidden rounded-2xl bg-black">
          <img :src="selectedEvidenceImage" alt="Preview Bukti KBM" class="w-full h-auto max-h-[75vh] object-contain rounded-xl" />
        </div>

        <div class="p-2 flex justify-between items-center text-xs text-slate-400">
          <span>{{ selectedEvidenceItem?.captured_at ? new Date(selectedEvidenceItem.captured_at).toLocaleString('id-ID') : '' }}</span>
          <button @click="selectedEvidenceImage = null" class="px-4 py-1.5 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-700 cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
