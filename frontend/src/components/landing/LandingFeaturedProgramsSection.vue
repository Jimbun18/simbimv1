<script setup lang="ts">
import { featuredPrograms, type FeaturedProgram } from './landingData'

defineProps<{
  showViewAllLink?: boolean
  sectionId?: string
}>()
</script>

<template>
  <section :id="sectionId || 'program-unggulan'" class="py-20 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="max-w-3xl space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            <span>⭐</span>
            <span>8 Program Unggulan Sekolah</span>
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
            Program Unggulan <br class="hidden sm:inline" />
            <span class="text-emerald-700">SDI Bina Insan Mandiri Purwokerto</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Kurikulum holistik berbasis <em>Deep Learning</em> yang mengasah kecerdasan spiritual, intelektual, kepemimpinan, dan kemandirian siswa/siswi untuk membentuk generasi pembina peradaban.
          </p>
        </div>

        <div v-if="showViewAllLink" class="shrink-0">
          <router-link
            to="/pendidikan#program-unggulan"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs sm:text-sm transition border border-emerald-200 shadow-xs"
          >
            <span>Buka di Halaman Pendidikan</span>
            <span>&rarr;</span>
          </router-link>
        </div>
      </div>

      <!-- Grid 8 Program Unggulan (4 Kolom pada Desktop, 2 pada Tablet, 1 pada Mobile) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="program in featuredPrograms" 
          :key="program.id"
          class="group bg-slate-50 hover:bg-white rounded-3xl border border-slate-200 hover:border-emerald-300 shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden"
        >
          <!-- FOTO / VISUAL CONTAINER PROGRAM -->
          <div class="relative h-48 w-full overflow-hidden bg-slate-900">
            <!-- Jika foto sudah diberikan user -->
            <template v-if="program.image">
              <img 
                :src="program.image" 
                :alt="program.title" 
                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
              />
              <!-- Soft Gradient Shadow Overlay -->
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-black/30 pointer-events-none"></div>

              <!-- Floating Badges di atas Foto -->
              <div class="absolute top-3 left-3 w-8 h-8 rounded-full bg-slate-950/90 text-white font-black text-xs flex items-center justify-center shadow-md z-10 border border-white/30 backdrop-blur-xs">
                {{ program.number }}
              </div>
              <div class="absolute top-3 right-3 z-10">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-black/50 backdrop-blur-md text-emerald-100 border border-white/20">
                  {{ program.category }}
                </span>
              </div>
              <div class="absolute bottom-2.5 left-3 right-3 z-10 text-white">
                <div class="text-[10px] font-mono text-emerald-300 font-semibold drop-shadow-sm flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                  <span>Dokumentasi Siswa/Siswi BIM</span>
                </div>
              </div>
            </template>
            
            <!-- Jika belum ada foto (Tampilan Banner Artistik Islami Menunggu Foto) -->
            <div 
              v-else 
              class="w-full h-full bg-gradient-to-br flex flex-col justify-between p-5 text-white relative overflow-hidden group-hover:scale-105 transition-transform duration-500"
              :class="program.gradient"
            >
              <!-- Ambient Pattern Effect -->
              <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
              
              <!-- Top Row: Badge Nomor & Kategori -->
              <div class="relative z-10 flex items-center justify-between">
                <!-- Lingkaran Nomor Hitam-Putih Sesuai Desain Acuan -->
                <div class="w-8 h-8 rounded-full bg-white text-slate-950 font-black text-sm flex items-center justify-center shadow-md">
                  {{ program.number }}
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-black/25 backdrop-blur-md text-emerald-100 border border-white/15">
                  {{ program.category }}
                </span>
              </div>

              <!-- Center/Bottom Info Banner -->
              <div class="relative z-10 space-y-1">
                <div class="text-3xl mb-1">{{ program.icon }}</div>
                <div class="text-xs font-mono text-emerald-200/90 font-medium tracking-wide">Program #{{ program.number }}</div>
                <h4 class="font-black text-base text-white leading-tight drop-shadow-sm">{{ program.title }}</h4>
              </div>
            </div>
          </div>

          <!-- KONTEN DESKRIPSI PROGRAM -->
          <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">
                  {{ program.number }}
                </span>
                <h3 class="font-black text-base text-slate-900 group-hover:text-emerald-700 transition">
                  {{ program.title }}
                </h3>
              </div>
              
              <p class="text-[11px] font-bold text-emerald-700 leading-snug">
                {{ program.subtitle }}
              </p>

              <p class="text-xs text-slate-600 leading-relaxed">
                {{ program.desc }}
              </p>
            </div>

            <div class="pt-3 border-t border-slate-200/80 flex items-center justify-between text-[11px]">
              <span class="font-semibold text-slate-500">SDI BIM Purwokerto</span>
              <span class="font-bold text-emerald-700 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                Deep Learning &rarr;
              </span>
            </div>
          </div>

        </div>
      </div>

      <!-- Bottom Banner Komitmen Pendidikan -->
      <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-950 via-teal-900 to-slate-950 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
        <div class="space-y-2 text-center md:text-left">
          <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-amber-400/20 text-amber-300 text-[11px] font-bold uppercase tracking-wider border border-amber-400/30">
            Sinergi Kurikulum Terpadu
          </div>
          <h3 class="text-lg sm:text-xl font-black text-white">
            Setiap Program Dirancang Tumbuh Bersama Potensi Siswa/Siswi
          </h3>
          <p class="text-xs text-emerald-100/90 max-w-2xl leading-relaxed">
            Menghubungkan kecintaan pada Al-Qur'an, adab berakhlak mulia, kecakapan bahasa global, hingga keterampilan hidup nyata (Cooking Day & Entrepreneurship).
          </p>
        </div>

        <router-link
          to="/spmb"
          class="px-6 py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs sm:text-sm transition shrink-0 shadow-lg shadow-amber-400/20"
        >
          Daftar Siswa/Siswi Baru (SPMB) &rarr;
        </router-link>
      </div>

    </div>
  </section>
</template>
