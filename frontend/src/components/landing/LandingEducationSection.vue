<script setup lang="ts">
import { deepLearningPillars, civilizationPoints } from './landingData'
import LandingFeaturedProgramsSection from './LandingFeaturedProgramsSection.vue'
import pendidikanBawahBg from '../../assets/pendidikan bawah.jpeg'
</script>

<template>
  <section id="pendidikan" class="py-20 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
      
      <!-- SUB-SECTION 1: PENDIDIKAN BERBASIS DEEP LEARNING -->
      <div id="pendidikan-deep-learning" class="space-y-10">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
          <span class="px-3.5 py-1 rounded-full bg-teal-100 text-teal-800 text-xs font-bold uppercase tracking-wider">
            Filosofi & Kurikulum Unggulan
          </span>
          <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Pendidikan Berbasis Deep Learning
          </h2>
          <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
            Mewujudkan pembelajaran mendalam melalui sinergi <strong>Mindful Learning</strong> (kesadaran batin & tauhid), <strong>Meaningful Learning</strong> (bermakna & kontekstual), dan <strong>Joyful Learning</strong> (menggembirakan) agar ilmu meresap kokoh di hati santri.
          </p>
        </div>

        <!-- 4 Pilar Deep Learning Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div 
            v-for="pillar in deepLearningPillars" 
            :key="pillar.title"
            class="bg-white p-7 sm:p-8 rounded-3xl border border-slate-200/90 shadow-xs hover:shadow-xl hover:border-emerald-300 transition-all space-y-4"
          >
            <div class="flex items-center justify-between">
              <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl shadow-xs border border-emerald-100">
                {{ pillar.icon }}
              </span>
              <span class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                Pilar Deep Learning
              </span>
            </div>

            <div>
              <h3 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">
                {{ pillar.title }}
              </h3>
              <p class="text-xs font-bold text-emerald-700 mt-1">
                {{ pillar.subtitle }}
              </p>
            </div>

            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              {{ pillar.desc }}
            </p>

            <div class="pt-3 border-t border-slate-100 space-y-2">
              <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                Praktik Nyata di SDI BIM:
              </div>
              <ul class="text-xs text-slate-700 space-y-1.5">
                <li v-for="(practice, idx) in pillar.practices" :key="idx" class="flex items-start gap-2">
                  <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                  <span>{{ practice }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

      </div>

      <!-- SUB-SECTION 2: 8 PROGRAM UNGGULAN SEKOLAH -->
      <div id="program-unggulan" class="pt-4">
        <LandingFeaturedProgramsSection section-id="pendidikan-program-unggulan" />
      </div>

      <!-- SUB-SECTION 3: TUJUAN PENDIDIKAN ADALAH MEMBANGUN SEBUAH PERADABAN -->
      <div id="membangun-peradaban" class="pt-8">
        <div class="rounded-3xl p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden">
          
          <!-- Background Image Pendidikan Bawah (Suasana KBM Kelas) -->
          <div class="absolute inset-0 z-0">
            <img 
              :src="pendidikanBawahBg" 
              alt="Suasana KBM Belajar Santri SDI BIM" 
              class="w-full h-full object-cover object-center scale-105 filter blur-[1.5px]"
            />
            <!-- Dark Emerald & Charcoal Gradient Overlay agar teks sangat tajam & kontras -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#022c22]/95 via-[#034435]/90 to-[#02261e]/95"></div>
            <div class="absolute inset-0 bg-slate-950/40"></div>
          </div>

          <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none z-0"></div>
          <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-amber-400/10 rounded-full blur-3xl pointer-events-none z-0"></div>

          <div class="relative z-10 max-w-3xl mx-auto text-center space-y-4 mb-12">
            <span class="px-3.5 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-bold uppercase tracking-wider">
              Orientasi Peradaban
            </span>
            <h3 class="text-2xl sm:text-3xl md:text-4xl font-black text-white leading-tight">
              Tujuan Pendidikan adalah <br class="hidden sm:inline" />
              <span class="text-amber-300">Membangun Sebuah Peradaban</span>
            </h3>
            <p class="text-xs sm:text-sm md:text-base text-emerald-100/90 leading-relaxed">
              Pendidikan bukan sekadar mencetak lulusan yang siap menjadi pekerja, melainkan membentuk manusia berjiwa khalifah yang memiliki aqidah lurus, akhlak agung, dan kepedulian tinggi untuk menebar rahmat bagi semesta alam.
            </p>
          </div>

          <!-- Grid 4 Titik Tumpu Peradaban -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 relative z-10">
            <div 
              v-for="point in civilizationPoints" 
              :key="point.title"
              class="p-5 rounded-2xl bg-white/10 border border-white/15 backdrop-blur-md hover:bg-white/15 transition-all space-y-3"
            >
              <div class="text-3xl">{{ point.icon }}</div>
              <h4 class="font-bold text-sm text-emerald-200 leading-snug">
                {{ point.title }}
              </h4>
              <p class="text-xs text-white/80 leading-relaxed font-light">
                {{ point.desc }}
              </p>
            </div>
          </div>

          <!-- Bottom Quote Box -->
          <div class="mt-10 p-5 rounded-2xl bg-black/30 border border-emerald-500/30 text-center max-w-2xl mx-auto relative z-10">
            <p class="text-xs sm:text-sm text-emerald-200 italic">
              "Jika engkau ingin menghancurkan sebuah peradaban, ada tiga caranya: hancurkan keluarganya, hancurkan pendidikannya, dan rendahkan tokoh panutannya. Maka di SDI BIM, kami memuliakan keluarga, menghidupkan pendidikan fitrah, dan meneladani Rasulullah SAW."
            </p>
          </div>

        </div>
      </div>

    </div>
  </section>
</template>
