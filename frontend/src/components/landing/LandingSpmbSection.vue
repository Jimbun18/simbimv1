<script setup lang="ts">
import { ref } from 'vue'
import { spmbSteps, spmbWaves, spmbRequirements } from './landingData'

const studentName = ref('')
const parentPhone = ref('')
const selectedClass = ref('Kelas 1 SD')
const notes = ref('')
const isSubmitted = ref(false)

const handleQuickRegister = () => {
  if (!studentName.value || !parentPhone.value) return

  // Format pesan WhatsApp ke Panitia SPMB
  const text = encodeURIComponent(
    `Bismillah, Assalamu'alaikum Panitia SPMB SDI Bina Insan Mandiri Purwokerto.\n\n` +
    `Saya berminat mendaftarkan ananda:\n` +
    `- Nama Calon Siswa: ${studentName.value}\n` +
    `- Rencana Masuk: ${selectedClass.value}\n` +
    `- Nomor WhatsApp Orang Tua: ${parentPhone.value}\n` +
    (notes.value ? `- Catatan/Pertanyaan: ${notes.value}\n\n` : `\n`) +
    `Mohon informasi lebih lanjut mengenai jadwal observasi fitrah dan formulir SPMB 2026/2027. Terima kasih.`
  )
  
  // Buka WhatsApp Sekretariat / Panitia
  window.open(`https://wa.me/6285138568694?text=${text}`, '_blank')
  isSubmitted.value = true
}
</script>

<template>
  <section id="spmb" class="py-20 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
      
      <!-- Section Title Header -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 50 }"
        class="text-center max-w-3xl mx-auto space-y-3"
      >
        <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
          Penerimaan Murid Baru
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Info SPMB Tahun Ajaran 2026/2027
        </h2>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          Bergabunglah bersama keluarga besar <strong>SDI Bina Insan Mandiri Purwokerto</strong>. Bersama kita rawat fitrah ananda untuk melahirkan generasi pembangun peradaban yang berakhlak Qur'ani.
        </p>
      </div>

      <!-- Jadwal Gelombang Pendaftaran Cards (2 Kolom Mobile, 3 Desktop) -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6">
        <div 
          v-for="(wave, idx) in spmbWaves" 
          :key="wave.name"
          v-reveal="{ type: 'fade-up', delay: 60 + idx * 90 }"
          class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border shadow-xs hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between"
          :class="[
            wave.isOpen ? 'border-emerald-300 ring-2 ring-emerald-500/20 shadow-md' : 'border-slate-200 opacity-90',
            idx === 2 ? 'col-span-2 md:col-span-1' : ''
          ]"
        >
          <div class="space-y-2 sm:space-y-3">
            <div class="flex items-center justify-between">
              <span 
                class="px-2 sm:px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider"
                :class="wave.isOpen ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
              >
                {{ wave.status }}
              </span>
              <span v-if="wave.isOpen" class="w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
            </div>

            <h3 class="font-black text-xs sm:text-base text-slate-900 leading-snug">
              {{ wave.name }}
            </h3>

            <div class="p-2 sm:p-3 bg-slate-50 rounded-xl border border-slate-100">
              <div class="text-[9px] sm:text-[10px] font-bold uppercase text-slate-400">Periode</div>
              <div class="text-[11px] sm:text-xs font-bold text-slate-800 font-mono mt-0.5 truncate">{{ wave.period }}</div>
            </div>

            <p class="text-[11px] sm:text-xs text-emerald-700 font-medium">
              ★ {{ wave.badge }}
            </p>
          </div>

          <div class="mt-3 sm:mt-5 pt-2 sm:pt-3 border-t border-slate-100 text-[10px] sm:text-[11px] text-slate-500">
            {{ wave.isOpen ? 'Maks. 28 siswa / rombel.' : 'Dibuka berikutnya.' }}
          </div>
        </div>
      </div>

      <!-- 5 Langkah Alur Pendaftaran (2 Kolom Mobile, 5 Desktop) -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 100 }"
        class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-10 border border-slate-200/90 shadow-xs space-y-6 sm:space-y-8"
      >
        <div class="text-center max-w-2xl mx-auto space-y-1">
          <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Tahapan Seleksi</span>
          <h3 class="text-lg sm:text-2xl font-black text-slate-900">
            5 Langkah Mudah Alur Pendaftaran SPMB
          </h3>
          <p class="text-xs text-slate-500">
            Proses observasi yang ramah anak tanpa tes calistung yang membebani.
          </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-4">
          <div 
            v-for="(step, idx) in spmbSteps" 
            :key="step.step"
            v-reveal="{ type: 'fade-up', delay: 80 + idx * 70 }"
            class="p-3.5 sm:p-5 rounded-xl sm:rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-300 hover:shadow-md hover:-translate-y-1 transition-all duration-300 space-y-1.5 sm:space-y-2 flex flex-col justify-between"
            :class="idx === 4 ? 'col-span-2 lg:col-span-1' : ''"
          >
            <div>
              <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg sm:rounded-xl bg-emerald-700 text-white font-black text-[10px] sm:text-xs flex items-center justify-center">
                  {{ step.step }}
                </span>
                <span class="text-lg sm:text-xl">{{ step.icon }}</span>
              </div>
              <h4 class="font-bold text-xs sm:text-xs text-slate-900 leading-snug">
                {{ step.title }}
              </h4>
            </div>
            <p class="text-[10px] sm:text-[11px] text-slate-600 leading-relaxed pt-1.5 sm:pt-2 border-t border-slate-200/60 font-light line-clamp-2 sm:line-clamp-none">
              {{ step.desc }}
            </p>
          </div>
        </div>
      </div>

      <!-- Syarat Berkas & Formulir WhatsApp Langsung -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Syarat & Ketentuan -->
        <div 
          v-reveal="{ type: 'slide-left', delay: 100 }"
          class="lg:col-span-6 bg-white rounded-3xl p-8 border border-slate-200/90 shadow-xs space-y-5"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-lg">
              📋
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900">Syarat & Berkas Pendaftaran</h3>
              <p class="text-xs text-slate-500">Persyaratan administrasi calon siswa baru</p>
            </div>
          </div>

          <ul class="space-y-2.5 text-xs sm:text-sm text-slate-700">
            <li v-for="(req, i) in spmbRequirements" :key="i" class="flex items-start gap-2.5">
              <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">
                ✓
              </span>
              <span>{{ req }}</span>
            </li>
          </ul>

          <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-xs text-emerald-900 space-y-1">
            <div class="font-bold flex items-center gap-1.5">
              <span>💡</span>
              <span>Catatan Observasi Fitrah</span>
            </div>
            <p class="text-[11px] text-emerald-800/90 leading-relaxed">
              Observasi bukan tes kelulusan berbasis akademik kaku, melainkan pemetaan awal gaya belajar, kematangan motorik, emosi, dan kecenderungan fitrah anak agar guru dapat mendampingi secara tepat sejak hari pertama sekolah.
            </p>
          </div>
        </div>

        <!-- Form Konsultasi & Pendaftaran WhatsApp -->
        <div 
          v-reveal="{ type: 'slide-right', delay: 150 }"
          class="lg:col-span-6 bg-gradient-to-br from-[#022e23] to-[#034435] rounded-3xl p-8 text-white shadow-xl space-y-6"
        >
          <div>
            <span class="px-3 py-1 rounded-full bg-emerald-400/20 text-emerald-300 border border-emerald-400/30 text-xs font-bold uppercase tracking-wider">
              Konsultasi Langsung
            </span>
            <h3 class="text-xl sm:text-2xl font-black text-white mt-2">
              Hubungi Panitia SPMB SDI BIM
            </h3>
            <p class="text-xs sm:text-sm text-emerald-100/90 mt-1 leading-relaxed">
              Isi data di bawah ini untuk terhubung langsung dengan Panitia SPMB via WhatsApp, mendapatkan brosur digital, dan jadwal observasi.
            </p>
          </div>

          <form @submit.prevent="handleQuickRegister" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-emerald-200 mb-1">Nama Lengkap Calon Siswa *</label>
              <input 
                v-model="studentName"
                type="text" 
                required
                placeholder="Contoh: Muhammad Fatih"
                class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/40 text-xs sm:text-sm focus:outline-hidden focus:ring-2 focus:ring-amber-400 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-emerald-200 mb-1">Nomor WhatsApp Orang Tua *</label>
              <input 
                v-model="parentPhone"
                type="tel" 
                required
                placeholder="Contoh: 081234567890"
                class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/40 text-xs sm:text-sm focus:outline-hidden focus:ring-2 focus:ring-amber-400 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-emerald-200 mb-1">Rencana Jenjang Pendaftaran</label>
              <select 
                v-model="selectedClass"
                class="w-full px-4 py-3 rounded-xl bg-emerald-950 border border-white/20 text-white text-xs sm:text-sm focus:outline-hidden focus:ring-2 focus:ring-amber-400 transition"
              >
                <option value="Kelas 1 SD">Kelas 1 SD (Siswa Baru)</option>
                <option value="Pindahan Kelas 2-5">Pindahan / Mutasi (Kelas 2 s/d 5)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-emerald-200 mb-1">Pertanyaan / Catatan Tambahan (Opsional)</label>
              <textarea 
                v-model="notes"
                rows="2"
                placeholder="Tuliskan pertanyaan seputar SPMB atau beasiswa..."
                class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/40 text-xs sm:text-sm focus:outline-hidden focus:ring-2 focus:ring-amber-400 transition"
              ></textarea>
            </div>

            <button 
              type="submit"
              v-splash="'amber'"
              class="btn-splash btn-sheen w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm shadow-xl shadow-amber-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Hubungi Panitia SPMB via WhatsApp</span>
              <svg class="w-4 h-4 text-slate-950" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.668-.699c.971.532 1.77.82 2.792.82 3.183 0 5.768-2.587 5.768-5.766.001-3.18-2.585-5.766-5.768-5.766zm9.969 5.828c0 5.518-4.482 10-10 10-1.748 0-3.385-.45-4.819-1.238l-7.181 1.882 1.916-6.993c-.886-1.488-1.396-3.23-1.396-5.091 0-5.518 4.482-10 10-10 5.518 0 10 4.482 10 10z"/>
              </svg>
            </button>
          </form>

          <div class="pt-2 border-t border-white/10 flex items-center justify-between text-xs text-emerald-300">
            <span>Kantor SPMB: 08:00 - 15:00 WIB</span>
            <span>Senin s/d Sabtu</span>
          </div>
        </div>

      </div>

    </div>
  </section>
</template>
