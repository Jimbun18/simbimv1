<script setup lang="ts">
import { ref, watch } from 'vue'
import { teachersData, studentPledges } from './landingData'

const props = defineProps<{
  activeTabProp?: 'profil' | 'visi' | 'guru' | 'janji'
}>()

const activeTab = ref<'profil' | 'visi' | 'guru' | 'janji'>(props.activeTabProp || 'profil')

watch(
  () => props.activeTabProp,
  (newVal) => {
    if (newVal) {
      activeTab.value = newVal
    }
  }
)
</script>

<template>
  <section id="profil" class="py-20 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Title Header -->
      <div class="text-center max-w-3xl mx-auto">
        <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
          Bagian 1 • Profil Lembaga
        </span>
        <h2 class="mt-3 text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          SDI Bina Insan Mandiri Purwokerto
        </h2>
        <p class="mt-3 text-sm sm:text-base text-slate-600 leading-relaxed">
          Mengenal lebih dekat identitas lembaga, cita-cita luhur visi-misi, dedikasi tenaga pendidik, serta ikrar janji siswa kami.
        </p>

        <!-- Sub-Navigation Tabs Switcher -->
        <div class="mt-8 flex flex-wrap items-center justify-center gap-2 p-1.5 bg-slate-100 rounded-2xl max-w-2xl mx-auto border border-slate-200">
          <button
            @click="activeTab = 'profil'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
            :class="activeTab === 'profil' ? 'bg-white text-emerald-800 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900'"
          >
            🏛️ Profil Singkat
          </button>
          <button
            @click="activeTab = 'visi'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
            :class="activeTab === 'visi' ? 'bg-white text-emerald-800 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900'"
          >
            🎯 Visi & Misi
          </button>
          <button
            @click="activeTab = 'guru'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
            :class="activeTab === 'guru' ? 'bg-white text-emerald-800 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900'"
          >
            👨‍🏫 Tenaga Pendidik
          </button>
          <button
            @click="activeTab = 'janji'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
            :class="activeTab === 'janji' ? 'bg-white text-emerald-800 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900'"
          >
            📜 Janji Siswa
          </button>
        </div>
      </div>

      <!-- TAB 1: PROFIL SINGKAT -->
      <div v-show="activeTab === 'profil'" id="profil-singkat" class="space-y-8 animate-fadeIn">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          
          <div class="lg:col-span-7 space-y-4">
            <h3 class="text-2xl font-black text-slate-900">
              Mendidik Generasi Islam Unggul Sejak 2012
            </h3>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
              <strong>SDI Bina Insan Mandiri Purwokerto</strong> berdiri di bawah naungan <strong>Yayasan Bina Insan Mandiri</strong>. Berlokasi strategis dan asri di Jl. Wadas Kelir RT.04 RW.05 Kelurahan Karangklesem, Kecamatan Purwokerto Selatan, Kabupaten Banyumas.
            </p>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
              Sebagai sekolah dasar Islam unggulan, kami memadukan Kurikulum Nasional, Kurikulum Berbasis Deep Learning (Mindful, Meaningful, & Joyful Learning), dan Kurikulum Khas Al-Qur'an (Tahsin Tartil & Tahfidz Mutqin).
            </p>

            <!-- Kartu Identitas Resmi -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-[10px] uppercase font-bold text-slate-400">NPSN</div>
                <div class="text-base font-black text-slate-800 font-mono">70036695</div>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-[10px] uppercase font-bold text-slate-400">Kurikulum</div>
                <div class="text-base font-black text-emerald-700">Deep Learning & Tematik</div>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-[10px] uppercase font-bold text-slate-400">Tahun Berdiri</div>
                <div class="text-base font-black text-slate-800 font-mono">2012</div>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-[10px] uppercase font-bold text-slate-400">Jenjang</div>
                <div class="text-base font-black text-slate-800">Sekolah Dasar (SD)</div>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-[10px] uppercase font-bold text-slate-400">Status</div>
                <div class="text-base font-black text-slate-800">Swasta / Mandiri</div>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-[10px] uppercase font-bold text-slate-400">Kecamatan</div>
                <div class="text-base font-black text-slate-800">Purwokerto Selatan</div>
              </div>
            </div>
          </div>

          <!-- Right Column: Sambutan Kepala Sekolah -->
          <div class="lg:col-span-5">
            <div class="bg-gradient-to-br from-emerald-900 to-teal-950 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden">
              <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/20 rounded-full blur-2xl"></div>
              
              <div class="flex items-center gap-3.5 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-2xl shrink-0">
                  🧕
                </div>
                <div>
                  <h4 class="font-bold text-base text-white">Rasiti, S.Pd.</h4>
                  <p class="text-xs text-emerald-300">Kepala Sekolah SDI Bina Insan Mandiri</p>
                </div>
              </div>

              <blockquote class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed italic border-l-2 border-emerald-400 pl-3 my-3">
                "Anak-anak bukan cangkir kosong yang siap diisi air apa saja, melainkan benih pohon peradaban yang di dalamnya telah tertanam potensi fitrah ketuhanan dan kebaikan. Tugas kami adalah menyiramnya dengan kasih sayang, memupuknya dengan ilmu yang bermanfaat, dan melindunginya dari racun zaman."
              </blockquote>

              <div class="pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-emerald-300">
                <span>SDI Bina Insan Mandiri</span>
                <span>Mendidik Sepenuh Jiwa</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- TAB 2: VISI & MISI -->
      <div v-show="activeTab === 'visi'" id="visi-misi" class="space-y-8 animate-fadeIn">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          <!-- Card Visi -->
          <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/90 hover:shadow-lg transition-all group flex flex-col justify-between">
            <div>
              <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-emerald-600/30">
                🔭
              </div>
              <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Cita-Cita Mulia</span>
              <h3 class="text-xl font-black text-slate-900 mt-1 mb-3">Visi Sekolah</h3>
              <p class="text-sm text-slate-700 leading-relaxed font-medium bg-white p-4 rounded-2xl border border-slate-100 shadow-2xs">
                "Terwujudnya Generasi Muslim Pembelajar yang Bertauhid Kokoh, Berakhlak Mulia, Tumbuh Sesuai Fitrahnya, Hafidz Al-Qur'an, dan Siap Membangun Peradaban."
              </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-200 text-xs text-slate-500">
              Visi peradaban berorientasi dunia & akhirat.
            </div>
          </div>

          <!-- Card Misi -->
          <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/90 hover:shadow-lg transition-all group flex flex-col justify-between">
            <div>
              <div class="w-14 h-14 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-teal-600/30">
                🎯
              </div>
              <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Langkah Nyata</span>
              <h3 class="text-xl font-black text-slate-900 mt-1 mb-3">Misi Unggulan</h3>
              <ul class="text-xs sm:text-sm text-slate-700 space-y-2.5">
                <li class="flex items-start gap-2">
                  <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                  <span>Menumbuhkan dan merawat fitrah keimanan, fitrah belajar, dan fitrah bakat peserta didik.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                  <span>Menyelenggarakan pembelajaran Al-Qur'an (Tahsin & Tahfidz) yang tartil dan mutqin.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                  <span>Menerapkan kurikulum tematik integratif yang berorientasi pada nalar kritis dan adab mulia.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">4</span>
                  <span>Membangun kolaborasi Tri Sentra: Keluarga di rumah, Guru di sekolah, dan Masjid di masyarakat.</span>
                </li>
              </ul>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-200 text-xs text-slate-500">
              4 Pilar Misi terukur dan terarah.
            </div>
          </div>

          <!-- Card Nilai Dasar -->
          <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/90 hover:shadow-lg transition-all group flex flex-col justify-between">
            <div>
              <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-amber-500/30">
                💎
              </div>
              <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Karakter Utama</span>
              <h3 class="text-xl font-black text-slate-900 mt-1 mb-3">Nilai BIM</h3>
              <div class="space-y-2.5">
                <div class="p-3 bg-white rounded-xl border border-slate-100">
                  <div class="font-bold text-xs text-slate-800">Qur'ani</div>
                  <div class="text-[11px] text-slate-500">Al-Qur'an sebagai pedoman rasa, pikir, dan perilaku.</div>
                </div>
                <div class="p-3 bg-white rounded-xl border border-slate-100">
                  <div class="font-bold text-xs text-slate-800">Integritas & Adab</div>
                  <div class="text-[11px] text-slate-500">Mendahulukan adab di atas ilmu, menjunjung tinggi kejujuran.</div>
                </div>
                <div class="p-3 bg-white rounded-xl border border-slate-100">
                  <div class="font-bold text-xs text-slate-800">Mandiri & Berdaya</div>
                  <div class="text-[11px] text-slate-500">Tangguh menyelesaikan tantangan dan berani memimpin kebaikan.</div>
                </div>
              </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-200 text-xs text-slate-500">
              Menjadi budaya harian civitas akademika.
            </div>
          </div>

        </div>
      </div>

      <!-- TAB 3: TENAGA PENDIDIK & KEPENDIDIKAN -->
      <div v-show="activeTab === 'guru'" id="tenaga-pendidik" class="space-y-8 animate-fadeIn">
        <div class="text-center max-w-2xl mx-auto">
          <h3 class="text-xl sm:text-2xl font-black text-slate-900">
            Pendidik Berjiwa Pengasuh, Menginspirasi Sepenuh Hati
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Didukung oleh dewan asatidz dan tenaga kependidikan profesional yang menguasai pedagogi ramah anak dan kurikulum berbasis deep learning.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div 
            v-for="teacher in teachersData" 
            :key="teacher.id"
            class="p-5 rounded-3xl bg-slate-50 border border-slate-200/90 hover:shadow-lg hover:border-emerald-300 transition-all flex flex-col justify-between"
          >
            <div>
              <!-- Avatar Circle -->
              <div 
                class="w-14 h-14 rounded-2xl bg-gradient-to-tr text-white flex items-center justify-center font-bold text-lg shadow-md mb-4"
                :class="teacher.avatarBg"
              >
                {{ teacher.name.charAt(0) }}
              </div>

              <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                {{ teacher.category }}
              </span>

              <h4 class="font-bold text-sm text-slate-900 mt-2 leading-snug">
                {{ teacher.name }}
              </h4>
              <p class="text-xs text-emerald-700 font-medium mt-0.5 leading-tight">
                {{ teacher.role }}
              </p>
              <div class="text-[11px] text-slate-400 mt-1">
                {{ teacher.education }}
              </div>

              <blockquote class="text-[11px] text-slate-600 mt-3 italic border-l-2 border-emerald-300 pl-2 leading-relaxed">
                "{{ teacher.quote }}"
              </blockquote>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-200/80 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
              <span>SDI Bina Insan Mandiri</span>
              <span class="text-emerald-700 font-bold">Aktif Mengajar</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: JANJI SISWA -->
      <div v-show="activeTab === 'janji'" id="janji-siswa" class="space-y-8 animate-fadeIn">
        <div class="bg-gradient-to-br from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl">
          <div class="max-w-3xl mx-auto text-center space-y-3 mb-10">
            <span class="px-3 py-1 rounded-full bg-emerald-400/20 text-emerald-300 border border-emerald-400/30 text-xs font-bold uppercase tracking-wider">
              Ikrar Santri Peradaban
            </span>
            <h3 class="text-2xl sm:text-3xl font-black text-white">
              6 Janji Siswa SDI Bina Insan Mandiri Purwokerto
            </h3>
            <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
              Ikrar khidmat yang diucapkan dan dihayati seluruh santri setiap apel pagi untuk meneguhkan komitmen taqwa, adab, dan kemandirian.
            </p>
          </div>

          <!-- Grid 6 Ikrar -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div 
              v-for="pledge in studentPledges" 
              :key="pledge.number"
              class="p-5 rounded-2xl bg-white/10 border border-white/15 backdrop-blur-md hover:bg-white/15 transition-all space-y-2.5"
            >
              <div class="flex items-center justify-between">
                <span class="w-8 h-8 rounded-xl bg-emerald-400 text-slate-950 font-black text-sm flex items-center justify-center">
                  {{ pledge.number }}
                </span>
                <span class="text-2xl">{{ pledge.icon }}</span>
              </div>
              <h4 class="font-bold text-sm text-emerald-200 leading-snug">
                {{ pledge.title }}
              </h4>
              <p class="text-xs text-white/90 leading-relaxed font-light">
                {{ pledge.desc }}
              </p>
            </div>
          </div>

          <div class="mt-8 text-center pt-6 border-t border-white/10 text-xs text-emerald-200/80">
            Semoga Allah Subhanahu wa Ta'ala meridhoi dan menguatkan ikhtiar setiap santri dalam menepati janji mulia ini.
          </div>
        </div>
      </div>

    </div>
  </section>
</template>
