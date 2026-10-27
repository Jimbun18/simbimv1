<script setup lang="ts">
import { achievementsData } from './landingData'
</script>

<template>
  <section id="prestasi" class="py-20 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Title Header -->
      <div class="text-center max-w-3xl mx-auto space-y-3">
        <span class="px-3.5 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-bold uppercase tracking-wider">
          Jejak Karya & Keunggulan
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Prestasi Santri & Sekolah
        </h2>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          Kompilasi dedikasi dan bakat unggul santri SDI Bina Insan Mandiri Purwokerto dalam bidang Al-Qur'an, sains, teknologi, serta seni budaya islami.
        </p>
      </div>

      <!-- Grid Prestasi Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="item in achievementsData" 
          :key="item.id"
          class="bg-slate-50 rounded-3xl border border-slate-200/90 p-6 hover:shadow-xl hover:border-amber-300 transition-all flex flex-col justify-between group"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span 
                class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                :class="item.badgeColor"
              >
                {{ item.rank }}
              </span>
              <span class="text-xs font-mono font-bold text-slate-400">{{ item.year }}</span>
            </div>

            <h3 class="font-black text-base text-slate-900 group-hover:text-amber-800 transition-colors leading-snug">
              {{ item.title }}
            </h3>

            <div class="p-3 bg-white rounded-xl border border-slate-200/70 space-y-1">
              <div class="text-[10px] uppercase font-bold text-slate-400">Penerima Penghargaan</div>
              <div class="text-xs font-bold text-slate-800">{{ item.recipient }}</div>
              <div class="text-[11px] text-emerald-700 font-medium">Tingkat {{ item.level }}</div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
              {{ item.desc }}
            </p>
          </div>

          <div class="mt-5 pt-3 border-t border-slate-200/80 flex items-center justify-between text-[11px] text-slate-500">
            <span>Bidang: {{ item.category }}</span>
            <span class="text-amber-600 font-bold">🏆 Berprestasi</span>
          </div>
        </div>
      </div>

      <!-- Testimoni Wali Murid Card Grid -->
      <div class="mt-14 pt-10 border-t border-slate-200/80">
        <div class="text-center max-w-2xl mx-auto mb-8">
          <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Suara Orang Tua</span>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
            Apa Kata Ayah Bunda Tentang SDI BIM?
          </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 rounded-3xl bg-emerald-50/60 border border-emerald-200/80 space-y-3 flex flex-col justify-between">
            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
              "Alhamdulillah, perkembangan hafalan ananda di SDI BIM sangat pesat tanpa paksaan. Yang paling membuat saya terharu adalah adab kesantunan dan inisiatif sholatnya yang tertib di rumah."
            </p>
            <div class="flex items-center gap-3 pt-2 border-t border-emerald-200/60">
              <div class="w-10 h-10 rounded-full bg-emerald-700 text-white font-bold flex items-center justify-center text-xs">
                AN
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">dr. Anita Nuraini</div>
                <div class="text-[10px] text-emerald-800">Wali Murid Ananda Fatih (Kelas 4)</div>
              </div>
            </div>
          </div>

          <div class="p-6 rounded-3xl bg-teal-50/60 border border-teal-200/80 space-y-3 flex flex-col justify-between">
            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
              "Pendidikan berbasis deep learning di SDI BIM benar-benar menghargai cara belajar dan keunikan anak. Guru-gurunya sangat sabar dan komunikatif melalui Portal SIM-BIM, sehingga kami di rumah selalu selaras mendidik."
            </p>
            <div class="flex items-center gap-3 pt-2 border-t border-teal-200/60">
              <div class="w-10 h-10 rounded-full bg-teal-700 text-white font-bold flex items-center justify-center text-xs">
                BS
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">Bambang Susanto, M.T.</div>
                <div class="text-[10px] text-teal-800">Wali Murid Ananda Aisyah (Kelas 5)</div>
              </div>
            </div>
          </div>

          <div class="p-6 rounded-3xl bg-amber-50/60 border border-amber-200/80 space-y-3 flex flex-col justify-between">
            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
              "Suasana sekolahnya sangat ramah anak dan asri. Ananda selalu pulang sekolah dengan wajah ceria dan cerita seru seputar eksperimen tematik hariannya. Pilihan terbaik untuk fondasi anak."
            </p>
            <div class="flex items-center gap-3 pt-2 border-t border-amber-200/60">
              <div class="w-10 h-10 rounded-full bg-amber-600 text-white font-bold flex items-center justify-center text-xs">
                DR
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">Dian Rahmawati, S.E.</div>
                <div class="text-[10px] text-amber-800">Wali Murid Ananda Bilal (Kelas 3)</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>
