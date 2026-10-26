<template>
  <div class="space-y-4">
    <!-- Control Header & Rombel Switcher Toolbar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4 no-print">
      <!-- Class Selector Pills -->
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pilih Rombel Kelas:</span>
          <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded-md border border-purple-200">
            TP 2026/2027 (Ganjil)
          </span>
        </div>
        <div v-if="loadingLookups" class="flex items-center gap-2 py-1 text-xs text-gray-400">
          <svg class="w-3.5 h-3.5 animate-spin text-purple-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>Memuat rombel kelas...</span>
        </div>
        <div v-else-if="classes.length === 0" class="text-xs text-amber-600 font-medium py-1">
          Tidak ada rombel kelas yang ditemukan
        </div>
        <div v-else class="flex flex-wrap items-center gap-1.5">
          <button
            v-for="cls in classes"
            :key="cls.id"
            @click="selectClass(cls.id)"
            :class="selectedClassId === cls.id ? 'bg-purple-600 text-white shadow-xs font-bold ring-2 ring-purple-600/30' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold border border-gray-200'"
            class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5"
          >
            <span>{{ cls.nama }}</span>
            <span class="text-[10px] opacity-75 font-mono">({{ cls.kode }})</span>
          </button>
        </div>
      </div>

      <!-- Actions Toolbar -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- View Mode Toggle -->
        <div class="bg-gray-100 p-0.5 rounded-xl flex items-center text-xs border border-gray-200">
          <button
            @click="scheduleViewMode = 'matrix'"
            :class="scheduleViewMode === 'matrix' ? 'bg-white text-gray-900 font-bold shadow-2xs' : 'text-gray-500 hover:text-gray-800'"
            class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1"
            title="Tampilan Matriks Grid Resmi (Sesuai Kertas Jadwal)"
          >
            <span>📊</span>
            <span class="hidden sm:inline">Matriks Resmi</span>
          </button>
          <button
            @click="scheduleViewMode = 'table'"
            :class="scheduleViewMode === 'table' ? 'bg-white text-gray-900 font-bold shadow-2xs' : 'text-gray-500 hover:text-gray-800'"
            class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1"
            title="Tampilan Daftar Tabel Linear"
          >
            <span>📋</span>
            <span class="hidden sm:inline">Daftar Baris</span>
          </button>
        </div>

        <!-- Print Button -->
        <button
          @click="printSchedule"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95"
          title="Cetak Jadwal Pelajaran Format Resmi A4 Landscape"
        >
          <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Cetak A4</span>
        </button>

        <!-- Quick Template Loader -->
        <button
          @click="applyOfficialTemplateToClass"
          :disabled="loadingSchedules"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 disabled:opacity-50"
          title="Terapkan Susunan Jadwal Standar (Lentera, Tematik, Shalat, Ekskul)"
        >
          <span>⚡</span>
          <span>Terapkan Template</span>
        </button>

        <!-- Clear Class Schedule -->
        <button
          @click="clearEntireClassSchedules"
          :disabled="loadingSchedules || schedules.length === 0"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer disabled:opacity-50"
          title="Kosongkan seluruh jadwal pada rombel kelas ini"
        >
          <span>🗑️</span>
          <span class="hidden md:inline">Kosongkan</span>
        </button>
      </div>
    </div>

    <!-- Alert / Tips Bar -->
    <div v-if="scheduleViewMode === 'matrix' && schedules.length === 0" class="p-3.5 bg-blue-50/90 border border-blue-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-blue-950 no-print">
      <div class="flex items-center gap-2">
        <span class="text-base">💡</span>
        <span>Jadwal kelas ini masih kosong. Klik <strong>"Terapkan Template"</strong> untuk mengisi otomatis seluruh slot resmi.</span>
      </div>
      <button
        @click="applyOfficialTemplateToClass"
        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition shrink-0 cursor-pointer text-xs"
      >
        Isi Otomatis Sekarang
      </button>
    </div>

    <div v-if="scheduleViewMode === 'matrix'" class="p-3 bg-amber-50/80 border border-amber-200/80 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs text-amber-900 no-print">
      <div class="flex items-center gap-2">
        <span class="text-base">🖱️</span>
        <span class="font-medium"><strong>Tips:</strong> Klik kotak jadwal untuk ubah tema, guru, atau atur jam (1x35, 2x35, 3x35).</span>
      </div>
      <div class="flex items-center gap-2">
        <!-- Toggle 2x35 Mode -->
        <button
          type="button"
          @click="modeSlot2x35 = !modeSlot2x35"
          :class="modeSlot2x35 ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-amber-300 hover:bg-amber-100/50'"
          class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
          title="Alihkan tampilan gabungan 2x35 atau rincian 1x35 per slot"
        >
          <span>{{ modeSlot2x35 ? '⚡ Format 2x35 (Ringkas)' : '📋 Format 1x35 (Rinci)' }}</span>
        </button>

        <!-- Toggle Fit Layar -->
        <button
          type="button"
          @click="tableScale = tableScale === 'compact' ? 'normal' : 'compact'"
          :class="tableScale === 'compact' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-purple-200 hover:bg-purple-50'"
          class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
          title="Ubah skala tabel agar pas layar atau ukuran normal"
        >
          <span>{{ tableScale === 'compact' ? '🔍 Fit Layar (Ramping)' : '📐 Ukuran Normal' }}</span>
        </button>

        <!-- Cetak -->
        <button
          type="button"
          @click="printSchedule"
          class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 transition flex items-center gap-1 cursor-pointer shadow-sm active:scale-95"
        >
          <span>🖨️ Cetak</span>
        </button>

        <span class="font-mono text-[11px] font-bold text-purple-700 bg-white/90 px-2.5 py-1 rounded-xl border border-amber-200">
          {{ selectedClass?.nama }}
        </span>
      </div>
    </div>

    <!-- VIEW A: MATRIKS GRID RESMI 10-KOLOM -->
    <div v-if="scheduleViewMode === 'matrix'" class="bg-white p-3 sm:p-5 rounded-2xl border border-gray-200 shadow-sm print-area">
      <!-- Timetable Header Title (Always printed) -->
      <div class="text-center mb-3">
        <h2 class="text-sm sm:text-base md:text-lg font-black text-gray-900 tracking-wider uppercase">
          JADWAL PELAJARAN {{ selectedClass?.nama?.toUpperCase() || 'KELAS' }}
        </h2>
        <p class="text-xs sm:text-sm font-bold text-gray-700 tracking-wider uppercase">
          TAHUN PELAJARAN 2026/2027
        </p>
      </div>

      <!-- The 10-Column Timetable Table -->
      <div class="overflow-x-auto">
        <table
          class="w-full border-collapse border border-amber-800 table-fixed select-none"
          :class="tableScale === 'compact' ? 'text-[10.5px] min-w-0' : 'text-xs min-w-[950px]'"
        >
          <colgroup>
            <col style="width: 3.5%;" />
            <col style="width: 7%;" />
            <col style="width: 13.5%;" />
            <col style="width: 7%;" />
            <col style="width: 13%;" />
            <col style="width: 13%;" />
            <col style="width: 13%;" />
            <col style="width: 13%;" />
            <col style="width: 6.5%;" />
            <col style="width: 10.5%;" />
          </colgroup>

          <thead>
            <tr class="bg-[#d97706] text-black font-extrabold uppercase text-[10.5px] sm:text-xs">
              <th class="border border-amber-800 py-1.5 px-0.5 text-center font-black">JP</th>
              <th class="border border-amber-800 py-1.5 px-0.5 text-center font-black">JAM</th>
              <th class="border border-amber-800 py-1.5 px-1 text-center font-black">SENIN</th>
              <th class="border border-amber-800 py-1.5 px-0.5 text-center font-black">JAM</th>
              <th class="border border-amber-800 py-1.5 px-1 text-center font-black">SELASA</th>
              <th class="border border-amber-800 py-1.5 px-1 text-center font-black">RABU</th>
              <th class="border border-amber-800 py-1.5 px-1 text-center font-black">KAMIS</th>
              <th class="border border-amber-800 py-1.5 px-1 text-center font-black">JUM'AT</th>
              <th class="border border-amber-800 py-1.5 px-0.5 text-center font-black">JAM</th>
              <th class="border border-amber-800 py-1.5 px-1 text-center font-black">SABTU</th>
            </tr>
          </thead>

          <tbody>
            <!-- ROW 1: 06.30 - 07.05 -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5">1 x 35</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 1, '06.30 - 07.05') }}</td>
              <td @click="openQuickSet('senin', 1)" :class="cellStyleClass('senin', 1)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 1)">
                <CellContent :hari="'senin'" :urutan="1" fallback="LENTERA" />
              </td>
              <td rowspan="2" class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap align-middle">
                {{ formatSlotTime('selasa', 1, '06.30 - 07.50') }}
              </td>
              <td @click="openQuickSet('selasa', 1)" :class="cellStyleClass('selasa', 1)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 1)">
                <CellContent :hari="'selasa'" :urutan="1" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('rabu', 1)" :class="cellStyleClass('rabu', 1)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 1)">
                <CellContent :hari="'rabu'" :urutan="1" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('kamis', 1)" :class="cellStyleClass('kamis', 1)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 1)">
                <CellContent :hari="'kamis'" :urutan="1" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('jumat', 1)" :class="cellStyleClass('jumat', 1)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 1)">
                <CellContent :hari="'jumat'" :urutan="1" fallback="LENTERA" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('sabtu', 1, '07.00 - 07.30') }}</td>
              <td @click="openQuickSet('sabtu', 1)" :class="cellStyleClass('sabtu', 1)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 1)">
                <CellContent :hari="'sabtu'" :urutan="1" fallback="Dzikir+ Senam" />
              </td>
            </tr>

            <!-- ROW 2: 07.05 - 07.40 -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5">1 x 35</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 2, '07.05 - 07.40') }}</td>
              <td @click="openQuickSet('senin', 2)" :class="cellStyleClass('senin', 2)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 2)">
                <CellContent :hari="'senin'" :urutan="2" fallback="Upacara" />
              </td>
              <td @click="openQuickSet('selasa', 2)" :class="cellStyleClass('selasa', 2)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 2)">
                <CellContent :hari="'selasa'" :urutan="2" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('rabu', 2)" :class="cellStyleClass('rabu', 2)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 2)">
                <CellContent :hari="'rabu'" :urutan="2" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('kamis', 2)" :class="cellStyleClass('kamis', 2)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 2)">
                <CellContent :hari="'kamis'" :urutan="2" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('jumat', 2)" :class="cellStyleClass('jumat', 2)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 2)">
                <CellContent :hari="'jumat'" :urutan="2" fallback="LENTERA" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('sabtu', 2, '07.30 - 08.30') }}</td>
              <td @click="openQuickSet('sabtu', 2)" :class="cellStyleClass('sabtu', 2)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 2)">
                <CellContent :hari="'sabtu'" :urutan="2" fallback="Ekstrakulikuler Pilihan" />
              </td>
            </tr>

            <!-- ROW 3: Asmaul Husna -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 3, '07.40 - 08.00') }}</td>
              <td @click="openQuickSet('senin', 3)" :class="cellStyleClass('senin', 3)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 3)">
                <CellContent :hari="'senin'" :urutan="3" fallback="Asmaul Husna" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('selasa', 3, '07.50 - 08.00') }}</td>
              <td @click="openQuickSet('selasa', 3)" :class="cellStyleClass('selasa', 3)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 3)">
                <CellContent :hari="'selasa'" :urutan="3" fallback="Asmaul Husna" />
              </td>
              <td @click="openQuickSet('rabu', 3)" :class="cellStyleClass('rabu', 3)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 3)">
                <CellContent :hari="'rabu'" :urutan="3" fallback="Asmaul Husna" />
              </td>
              <td @click="openQuickSet('kamis', 3)" :class="cellStyleClass('kamis', 3)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 3)">
                <CellContent :hari="'kamis'" :urutan="3" fallback="Asmaul Husna" />
              </td>
              <td @click="openQuickSet('jumat', 3)" :class="cellStyleClass('jumat', 3)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 3)">
                <CellContent :hari="'jumat'" :urutan="3" fallback="Asmaul Husna" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('sabtu', 3, '08.25 - 08.30') }}</td>
              <td @click="openQuickSet('sabtu', 3)" :class="cellStyleClass('sabtu', 3)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 3)">
                <CellContent :hari="'sabtu'" :urutan="3" fallback="Asmaul Husna" />
              </td>
            </tr>

            <!-- ROW 4 & 5: SESI PAGI (COMBINED 2x35 OR DETAILED 1x35) -->
            <tr v-if="modeSlot2x35">
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5 font-sans">2 x 35</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 4, '08.00 - 09.10') }}</td>
              <td @click="openQuickSet('senin', 4)" :class="cellStyleClass('senin', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 4)">
                <CellContent :hari="'senin'" :urutan="4" fallback="TEMA 3" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('selasa', 4, '08.00 - 09.10') }}</td>
              <td @click="openQuickSet('selasa', 4)" :class="cellStyleClass('selasa', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 4)">
                <CellContent :hari="'selasa'" :urutan="4" fallback="TEMA 1" />
              </td>
              <td @click="openQuickSet('rabu', 4)" :class="cellStyleClass('rabu', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 4)">
                <CellContent :hari="'rabu'" :urutan="4" fallback="TEMA 4" />
              </td>
              <td @click="openQuickSet('kamis', 4)" :class="cellStyleClass('kamis', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 4)">
                <CellContent :hari="'kamis'" :urutan="4" fallback="TEMA 5" />
              </td>
              <td @click="openQuickSet('jumat', 4)" :class="cellStyleClass('jumat', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 4)">
                <CellContent :hari="'jumat'" :urutan="4" fallback="TEMA 2" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('sabtu', 4, '08.30 - 09.30') }}</td>
              <td @click="openQuickSet('sabtu', 4)" :class="cellStyleClass('sabtu', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 4)">
                <CellContent :hari="'sabtu'" :urutan="4" fallback="Ekstrakulikuler Wajib" />
              </td>
            </tr>
            <template v-else>
              <!-- ROW 4: 08.00 - 08.35 (1x35) -->
              <tr>
                <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5">1 x 35</td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 4, '08.00 - 08.35') }}</td>
                <td @click="openQuickSet('senin', 4)" :class="cellStyleClass('senin', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 4)">
                  <CellContent :hari="'senin'" :urutan="4" fallback="TEMA 3" />
                </td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('selasa', 4, '08.00 - 09.10') }}</td>
                <td @click="openQuickSet('selasa', 4)" :class="cellStyleClass('selasa', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 4)">
                  <CellContent :hari="'selasa'" :urutan="4" fallback="TEMA 1" />
                </td>
                <td @click="openQuickSet('rabu', 4)" :class="cellStyleClass('rabu', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 4)">
                  <CellContent :hari="'rabu'" :urutan="4" fallback="TEMA 4" />
                </td>
                <td @click="openQuickSet('kamis', 4)" :class="cellStyleClass('kamis', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 4)">
                  <CellContent :hari="'kamis'" :urutan="4" fallback="TEMA 5" />
                </td>
                <td @click="openQuickSet('jumat', 4)" :class="cellStyleClass('jumat', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 4)">
                  <CellContent :hari="'jumat'" :urutan="4" fallback="TEMA 2" />
                </td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('sabtu', 4, '08.30 - 09.00') }}</td>
                <td @click="openQuickSet('sabtu', 4)" :class="cellStyleClass('sabtu', 4)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 4)">
                  <CellContent :hari="'sabtu'" :urutan="4" fallback="Ekstrakulikuler Wajib" />
                </td>
              </tr>

              <!-- ROW 5: 08.35 - 09.10 (1x35) -->
              <tr>
                <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5">1 x 35</td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 5, '08.35 - 09.10') }}</td>
                <td @click="openQuickSet('senin', 5)" :class="cellStyleClass('senin', 5)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 5)">
                  <CellContent :hari="'senin'" :urutan="5" fallback="TEMA 3" />
                </td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 5, '08.35 - 09.10') }}</td>
                <td @click="openQuickSet('selasa', 5)" :class="cellStyleClass('selasa', 5)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 5)">
                  <CellContent :hari="'selasa'" :urutan="5" fallback="TEMA 1" />
                </td>
                <td @click="openQuickSet('rabu', 5)" :class="cellStyleClass('rabu', 5)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 5)">
                  <CellContent :hari="'rabu'" :urutan="5" fallback="TEMA 4" />
                </td>
                <td @click="openQuickSet('kamis', 5)" :class="cellStyleClass('kamis', 5)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 5)">
                  <CellContent :hari="'kamis'" :urutan="5" fallback="TEMA 5" />
                </td>
                <td @click="openQuickSet('jumat', 5)" :class="cellStyleClass('jumat', 5)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 5)">
                  <CellContent :hari="'jumat'" :urutan="5" fallback="TEMA 2" />
                </td>
                <td class="border border-amber-800 bg-[#fde047]"></td>
                <td class="border border-amber-800 bg-white"></td>
              </tr>
            </template>

            <!-- ROW 6: Shalat Dhuha + Istirahat -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 6, '09.10 - 09.40') }}</td>
              <td @click="openQuickSet('senin', 6)" :class="cellStyleClass('senin', 6)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 6)">
                <CellContent :hari="'senin'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 6, '09.10 - 09.40') }}</td>
              <td @click="openQuickSet('selasa', 6)" :class="cellStyleClass('selasa', 6)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 6)">
                <CellContent :hari="'selasa'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
              </td>
              <td @click="openQuickSet('rabu', 6)" :class="cellStyleClass('rabu', 6)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 6)">
                <CellContent :hari="'rabu'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
              </td>
              <td @click="openQuickSet('kamis', 6)" :class="cellStyleClass('kamis', 6)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 6)">
                <CellContent :hari="'kamis'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
              </td>
              <td @click="openQuickSet('jumat', 6)" :class="cellStyleClass('jumat', 6)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 6)">
                <CellContent :hari="'jumat'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('sabtu', 6, '09.30 - 10.00') }}</td>
              <td @click="openQuickSet('sabtu', 6)" :class="cellStyleClass('sabtu', 6)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 6)">
                <CellContent :hari="'sabtu'" :urutan="6" fallback="Ekstrakulikuler Wajib" />
              </td>
            </tr>

            <!-- ROW 7: 3X35 (09.40 - 11.20) -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5 font-sans">3X35</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 7, '09.40 - 11.20') }}</td>
              <td @click="openQuickSet('senin', 7)" :class="cellStyleClass('senin', 7)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 7)">
                <CellContent :hari="'senin'" :urutan="7" fallback="TEMA 3" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 7, '09.40 - 11.20') }}</td>
              <td @click="openQuickSet('selasa', 7)" :class="cellStyleClass('selasa', 7)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 7)">
                <CellContent :hari="'selasa'" :urutan="7" fallback="TEMA 1" />
              </td>
              <td @click="openQuickSet('rabu', 7)" :class="cellStyleClass('rabu', 7)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 7)">
                <CellContent :hari="'rabu'" :urutan="7" fallback="TEMA 4" />
              </td>
              <td @click="openQuickSet('kamis', 7)" :class="cellStyleClass('kamis', 7)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 7)">
                <CellContent :hari="'kamis'" :urutan="7" fallback="TEMA 5" />
              </td>
              <td @click="openQuickSet('jumat', 7)" :class="cellStyleClass('jumat', 7)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 7)">
                <CellContent :hari="'jumat'" :urutan="7" fallback="TEMA 2" />
              </td>
              <td :rowspan="modeSlot2x35 ? 3 : 4" colspan="2" class="border border-amber-800 bg-[#cbd5e1] p-2 sm:p-2.5 text-left align-top select-none font-sans">
                <div class="text-xs font-bold text-gray-900 mb-1.5">Catatan:</div>
                <div class="text-[10px] sm:text-[11px] text-gray-800 space-y-1 font-medium leading-relaxed">
                  <div><strong class="font-bold text-gray-900">Kamis:</strong> Javanese language</div>
                  <div><strong class="font-bold text-gray-900">Jum'at:</strong> Arabic language</div>
                </div>
              </td>
            </tr>

            <!-- ROW 8: Shalat Dzuhur + Makan siang -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 8, '11.20 - 12.10') }}</td>
              <td @click="openQuickSet('senin', 8)" :class="cellStyleClass('senin', 8)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 8)">
                <CellContent :hari="'senin'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 8, '11.20 - 12.10') }}</td>
              <td @click="openQuickSet('selasa', 8)" :class="cellStyleClass('selasa', 8)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 8)">
                <CellContent :hari="'selasa'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
              </td>
              <td @click="openQuickSet('rabu', 8)" :class="cellStyleClass('rabu', 8)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 8)">
                <CellContent :hari="'rabu'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
              </td>
              <td @click="openQuickSet('kamis', 8)" :class="cellStyleClass('kamis', 8)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 8)">
                <CellContent :hari="'kamis'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
              </td>
              <td @click="openQuickSet('jumat', 8)" :class="cellStyleClass('jumat', 8)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 8)">
                <CellContent :hari="'jumat'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
              </td>
            </tr>

            <!-- ROW 9 & 10: SESI SIANG (COMBINED 2x35 OR DETAILED 1x35) -->
            <tr v-if="modeSlot2x35">
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5 font-sans">2 x 35</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 9, '12.10 - 13.20') }}</td>
              <td @click="openQuickSet('senin', 9)" :class="cellStyleClass('senin', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 9)">
                <CellContent :hari="'senin'" :urutan="9" fallback="TEMA 3" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('selasa', 9, '12.10 - 13.20') }}</td>
              <td @click="openQuickSet('selasa', 9)" :class="cellStyleClass('selasa', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 9)">
                <CellContent :hari="'selasa'" :urutan="9" fallback="TEMA 1" />
              </td>
              <td @click="openQuickSet('rabu', 9)" :class="cellStyleClass('rabu', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 9)">
                <CellContent :hari="'rabu'" :urutan="9" fallback="TEMA 1" />
              </td>
              <td @click="openQuickSet('kamis', 9)" :class="cellStyleClass('kamis', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 9)">
                <CellContent :hari="'kamis'" :urutan="9" fallback="TEMA 5" />
              </td>
              <td @click="openQuickSet('jumat', 9)" :class="cellStyleClass('jumat', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 9)">
                <CellContent :hari="'jumat'" :urutan="9" fallback="TEMA 2" />
              </td>
            </tr>
            <template v-else>
              <!-- ROW 9: 12.10 - 12.45 (1x35) -->
              <tr>
                <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5">1 x 35</td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 9, '12.10 - 12.45') }}</td>
                <td @click="openQuickSet('senin', 9)" :class="cellStyleClass('senin', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 9)">
                  <CellContent :hari="'senin'" :urutan="9" fallback="TEMA 3" />
                </td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 9, '12.10 - 12.45') }}</td>
                <td @click="openQuickSet('selasa', 9)" :class="cellStyleClass('selasa', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 9)">
                  <CellContent :hari="'selasa'" :urutan="9" fallback="TEMA 1" />
                </td>
                <td @click="openQuickSet('rabu', 9)" :class="cellStyleClass('rabu', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 9)">
                  <CellContent :hari="'rabu'" :urutan="9" fallback="TEMA 1" />
                </td>
                <td @click="openQuickSet('kamis', 9)" :class="cellStyleClass('kamis', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 9)">
                  <CellContent :hari="'kamis'" :urutan="9" fallback="TEMA 5" />
                </td>
                <td @click="openQuickSet('jumat', 9)" :class="cellStyleClass('jumat', 9)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 9)">
                  <CellContent :hari="'jumat'" :urutan="9" fallback="TEMA 2" />
                </td>
              </tr>

              <!-- ROW 10: 12.45 - 13.20 (1x35) -->
              <tr>
                <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-1.5">1 x 35</td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 10, '12.45 - 13.20') }}</td>
                <td @click="openQuickSet('senin', 10)" :class="cellStyleClass('senin', 10)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 10)">
                  <CellContent :hari="'senin'" :urutan="10" fallback="TEMA 3" />
                </td>
                <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 10, '12.45 - 13.20') }}</td>
                <td @click="openQuickSet('selasa', 10)" :class="cellStyleClass('selasa', 10)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 10)">
                  <CellContent :hari="'selasa'" :urutan="10" fallback="TEMA 1" />
                </td>
                <td @click="openQuickSet('rabu', 10)" :class="cellStyleClass('rabu', 10)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 10)">
                  <CellContent :hari="'rabu'" :urutan="10" fallback="TEMA 2" />
                </td>
                <td @click="openQuickSet('kamis', 10)" :class="cellStyleClass('kamis', 10)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 10)">
                  <CellContent :hari="'kamis'" :urutan="10" fallback="TEMA 5" />
                </td>
                <td @click="openQuickSet('jumat', 10)" :class="cellStyleClass('jumat', 10)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 10)">
                  <CellContent :hari="'jumat'" :urutan="10" fallback="TEMA 2" />
                </td>
              </tr>
            </template>

            <!-- ROW 11: 13.20 - 13.30 (Lentera Refleksi) -->
            <tr>
              <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 11, '13.20 - 13.30') }}</td>
              <td @click="openQuickSet('senin', 11)" :class="cellStyleClass('senin', 11)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 11)">
                <CellContent :hari="'senin'" :urutan="11" fallback="LENTERA" />
              </td>
              <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[10.5px] whitespace-nowrap">{{ formatSlotTime('senin', 11, '13.20 - 13.30') }}</td>
              <td @click="openQuickSet('selasa', 11)" :class="cellStyleClass('selasa', 11)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 11)">
                <CellContent :hari="'selasa'" :urutan="11" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('rabu', 11)" :class="cellStyleClass('rabu', 11)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 11)">
                <CellContent :hari="'rabu'" :urutan="11" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('kamis', 11)" :class="cellStyleClass('kamis', 11)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 11)">
                <CellContent :hari="'kamis'" :urutan="11" fallback="LENTERA" />
              </td>
              <td @click="openQuickSet('jumat', 11)" :class="cellStyleClass('jumat', 11)" class="border border-amber-800 p-1 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 11)">
                <CellContent :hari="'jumat'" :urutan="11" fallback="LENTERA" />
              </td>
              <td class="border border-amber-800 bg-[#fde047]"></td>
              <td class="border border-amber-800 bg-white"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Color Legend & Guidance -->
      <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500 no-print">
        <div class="flex flex-wrap items-center gap-2">
          <span class="font-bold text-gray-700">Keterangan Warna:</span>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#86efac] text-emerald-950 font-bold border border-emerald-300">
            <span>Tema 3</span>
          </span>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#fca5a5] text-red-950 font-bold border border-red-300">
            <span>Tema 1</span>
          </span>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#fed7aa] text-amber-950 font-bold border border-amber-300">
            <span>Tema 4</span>
          </span>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#e0e7ff] text-indigo-950 font-bold border border-indigo-200">
            <span>Tema 5</span>
          </span>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#7dd3fc] text-sky-950 font-bold border border-sky-300">
            <span>Tema 2</span>
          </span>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-white text-gray-800 font-bold border border-gray-300">
            <span>Pembiasaan & Ibadah</span>
          </span>
        </div>
        <div class="text-[11px] text-gray-400">
          Total Sesi Terdaftar: <strong class="text-gray-800 font-mono">{{ schedules.length }} slot</strong>
        </div>
      </div>
    </div>

    <!-- VIEW B: DAFTAR TABEL LINEAR -->
    <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-900">
          Daftar Sesi Jadwal Kelas {{ selectedClass?.nama }}
        </h3>
        <button
          @click="$emit('create')"
          class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition cursor-pointer"
        >
          + Tambah Jadwal Manual
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 min-w-[700px]">
          <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3 px-4">Hari</th>
              <th class="py-3 px-4">Urutan / Waktu</th>
              <th class="py-3 px-4">Kegiatan / Pelajaran</th>
              <th class="py-3 px-4">Tipe</th>
              <th class="py-3 px-4">Guru Pengampu</th>
              <th class="py-3 px-4">Ruangan</th>
              <th class="py-3 px-4">Catatan</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="sch in sortedSchedules" :key="sch.id" class="hover:bg-gray-50/70 transition">
              <td class="py-2.5 px-4 font-bold capitalize text-purple-700">{{ sch.hari }}</td>
              <td class="py-2.5 px-4 font-mono whitespace-nowrap">
                <span class="font-bold text-gray-900">Slot {{ sch.time_slot?.urutan || '-' }}</span>
                <span class="text-gray-400 text-[11px] block">{{ sch.time_slot?.jam_mulai?.slice(0, 5) ?? '-' }} - {{ sch.time_slot?.jam_selesai?.slice(0, 5) ?? '-' }}</span>
              </td>
              <td class="py-2.5 px-4">
                <div class="font-bold text-gray-900">
                  {{ sch.judul_kegiatan || sch.subject?.nama || '-' }}
                </div>
                <span v-if="sch.subject?.is_tematik" class="px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded text-[9px] font-bold border border-purple-200">
                  Tema {{ sch.subject?.tema_nomor }}
                </span>
              </td>
              <td class="py-2.5 px-4">
                <span
                  :class="{
                    'bg-emerald-50 text-emerald-700': sch.tipe_kegiatan === 'kbm',
                    'bg-blue-50 text-blue-700': sch.tipe_kegiatan === 'pembiasaan',
                    'bg-amber-50 text-amber-700': sch.tipe_kegiatan === 'istirahat',
                    'bg-purple-50 text-purple-700': sch.tipe_kegiatan === 'ekskul',
                  }"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                >
                  {{ sch.tipe_kegiatan || 'kbm' }}
                </span>
              </td>
              <td class="py-2.5 px-4">{{ sch.teacher?.nama_lengkap ?? '-' }}</td>
              <td class="py-2.5 px-4 font-mono">{{ sch.room?.nama ?? sch.school_class?.room?.nama ?? '-' }}</td>
              <td class="py-2.5 px-4 text-gray-500 italic">{{ sch.catatan || '-' }}</td>
              <td class="py-2.5 px-4 text-right space-x-1 whitespace-nowrap">
                <button
                  @click="$emit('edit', sch)"
                  class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg font-semibold transition border border-amber-200 text-[10px] cursor-pointer"
                >
                  Edit
                </button>
                <button
                  @click="handleDeleteSchedule(sch)"
                  class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-semibold transition border border-rose-200 text-[10px] cursor-pointer"
                >
                  Hapus
                </button>
              </td>
            </tr>
            <tr v-if="schedules.length === 0">
              <td colspan="8" class="py-12 text-center text-gray-400">
                Belum ada data jadwal untuk kelas ini. Klik "Terapkan Template" untuk mengisi otomatis jadwal resmi.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- QUICK SET MODAL -->
    <div
      v-if="isQuickSetModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 no-print animate-in fade-in duration-150"
      @click.self="isQuickSetModalOpen = false"
    >
      <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-amber-500/10 via-purple-500/5 to-white">
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-amber-500 text-white font-black flex items-center justify-center text-sm shadow-sm uppercase">
              {{ quickSetForm.hari?.slice(0, 3) }}
            </span>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-black text-gray-900 leading-tight">
                  Atur Sesi: {{ quickSetForm.hari?.toUpperCase() }}
                </h3>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-100 text-purple-800">
                  {{ selectedClass?.nama || 'Kelas' }}
                </span>
              </div>
              <p class="text-xs text-gray-500 font-mono mt-0.5">
                {{ quickSetForm.jam_label }} • Slot Urutan ke-{{ quickSetForm.slot_urutan }}
              </p>
            </div>
          </div>
          <button
            @click="isQuickSetModalOpen = false"
            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 flex items-center justify-center transition cursor-pointer font-bold text-lg"
          >
            &times;
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-5 overflow-y-auto space-y-4">
          <!-- Waktu & Jam Sesi -->
          <div class="bg-amber-50/70 p-3.5 rounded-2xl border border-amber-200/80 space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
                <span>⏰</span>
                <span>Atur Jam Pelajaran (JP) & Waktu Sesi</span>
              </span>
              <span class="text-[10px] text-amber-800 font-mono font-bold bg-white/80 px-2 py-0.5 rounded border border-amber-200">
                Slot #{{ quickSetForm.slot_urutan }} • {{ quickSetForm.hari?.toUpperCase() }}
              </span>
            </div>

            <!-- JP Preset Selection Buttons -->
            <div>
              <label class="text-[10px] font-bold text-amber-900 block mb-1">Pilih Alokasi Jam Pelajaran (1 JP = 35 Menit):</label>
              <div class="grid grid-cols-4 gap-1.5">
                <button
                  type="button"
                  @click="setJpDuration(1)"
                  :class="detectedJp === 1 ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-white hover:bg-amber-100/70 text-gray-800 border border-amber-300 font-bold'"
                  class="py-1 px-1.5 text-center text-xs rounded-xl transition cursor-pointer"
                >
                  1 x 35 <span class="text-[9px] block opacity-80 font-normal">35 mnt</span>
                </button>
                <button
                  type="button"
                  @click="setJpDuration(2)"
                  :class="detectedJp === 2 ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-white hover:bg-amber-100/70 text-gray-800 border border-amber-300 font-bold'"
                  class="py-1 px-1.5 text-center text-xs rounded-xl transition cursor-pointer"
                >
                  2 x 35 <span class="text-[9px] block opacity-80 font-normal">70 mnt</span>
                </button>
                <button
                  type="button"
                  @click="setJpDuration(3)"
                  :class="detectedJp === 3 ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-white hover:bg-amber-100/70 text-gray-800 border border-amber-300 font-bold'"
                  class="py-1 px-1.5 text-center text-xs rounded-xl transition cursor-pointer"
                >
                  3 x 35 <span class="text-[9px] block opacity-80 font-normal">105 mnt</span>
                </button>
                <button
                  type="button"
                  @click="quickSetDurationMode = 'custom'"
                  :class="detectedJp === 0 ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-white hover:bg-amber-100/70 text-gray-800 border border-amber-300 font-bold'"
                  class="py-1 px-1.5 text-center text-xs rounded-xl transition cursor-pointer"
                >
                  Custom <span class="text-[9px] block opacity-80 font-normal">Manual</span>
                </button>
              </div>
            </div>

            <!-- Jam Mulai & Selesai Input -->
            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-amber-200/60">
              <div>
                <label class="text-[10px] font-bold text-gray-600 block mb-0.5">Jam Mulai:</label>
                <input
                  v-model="quickSetForm.jam_mulai"
                  type="time"
                  @change="onJamMulaiChange"
                  class="w-full bg-white border border-amber-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-amber-500 outline-none"
                />
              </div>
              <div>
                <label class="text-[10px] font-bold text-gray-600 block mb-0.5">Jam Selesai:</label>
                <input
                  v-model="quickSetForm.jam_selesai"
                  type="time"
                  class="w-full bg-white border border-amber-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-amber-500 outline-none"
                />
              </div>
            </div>
            <div class="flex items-center justify-between text-[10px] text-amber-800/90 font-medium px-0.5">
              <span>Total durasi: <strong>{{ calculatedDurationText }}</strong></span>
              <span v-if="detectedJp > 0" class="font-bold text-amber-900 bg-amber-200/60 px-1.5 py-0.5 rounded">
                {{ detectedJp }} x 35 menit
              </span>
            </div>
          </div>

          <!-- 1-Click Preset Chips -->
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">
              Pilihan Cepat (1-Klik Terapkan Tema / Kegiatan)
            </label>

            <!-- Tematik Pills -->
            <div class="flex flex-wrap gap-1.5">
              <button
                type="button"
                @click="applyPreset('TEMA 1')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#fca5a5] hover:bg-[#f87171] text-red-950 border border-red-300 transition cursor-pointer"
              >
                TEMA 1
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 2')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#7dd3fc] hover:bg-[#38bdf8] text-sky-950 border border-sky-300 transition cursor-pointer"
              >
                TEMA 2
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 3')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#86efac] hover:bg-[#4ade80] text-emerald-950 border border-emerald-300 transition cursor-pointer"
              >
                TEMA 3
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 4')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#fed7aa] hover:bg-[#fb923c] text-amber-950 border border-amber-300 transition cursor-pointer"
              >
                TEMA 4
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 5')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#e0e7ff] hover:bg-[#c7d2fe] text-indigo-950 border border-indigo-200 transition cursor-pointer"
              >
                TEMA 5
              </button>
            </div>

            <!-- Pembiasaan & Ibadah Pills -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <button
                type="button"
                v-for="p in ['LENTERA', 'Upacara', 'Asmaul Husna', 'Shalat Dhuha + Istirahat', 'Shalat Dzuhur + Makan siang', 'Dzikir+ Senam', 'Ekstrakulikuler Pilihan', 'Ekstrakulikuler Wajib']"
                :key="p"
                @click="applyPreset(p)"
                class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 transition cursor-pointer"
              >
                {{ p }}
              </button>
            </div>
          </div>

          <div class="border-t border-gray-100 pt-3 space-y-3">
            <!-- Mata Pelajaran Dropdown -->
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Mata Pelajaran (Hubungkan ke Kurikulum)</label>
              <select
                v-model="quickSetForm.subject_id"
                @change="onSubjectChange"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Bukan Mapel Tertentu (Lentera / Shalat / Senam) --</option>
                <optgroup label="Pelajaran Tematik">
                  <option v-for="s in subjectsList.filter(sub => sub.is_tematik)" :key="s.id" :value="s.id">
                    {{ s.display_name || s.nama }} ({{ s.kode }})
                  </option>
                </optgroup>
                <optgroup label="Pelajaran Reguler">
                  <option v-for="s in subjectsList.filter(sub => !sub.is_tematik)" :key="s.id" :value="s.id">
                    {{ s.nama }} ({{ s.kode }})
                  </option>
                </optgroup>
              </select>
            </div>

            <!-- Judul Kegiatan Input -->
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Judul / Teks Kegiatan di Jadwal *</label>
              <input
                v-model="quickSetForm.judul_kegiatan"
                type="text"
                placeholder="Contoh: TEMA 1, Matematika, LENTERA..."
                required
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <!-- Guru Pengampu -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-bold text-gray-700">Guru Pengampu</label>
                <span v-if="quickSetForm.teacher_id" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                  ✓ Terhubung ke Guru
                </span>
              </div>
              <select
                v-model="quickSetForm.teacher_id"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white font-semibold text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Tanpa Guru Pengampu Khusus --</option>
                <option v-for="t in teachersList" :key="t.id" :value="t.id">
                  {{ t.nama_lengkap }} ({{ t.jabatan || 'Guru' }})
                </option>
              </select>
              <p class="text-[11px] text-gray-500 mt-1">
                Guru yang dipilih akan langsung tampil di sel jadwal kelas ini dan tercatat dalam data mengajar.
              </p>
            </div>

            <!-- Catatan Khusus -->
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Khusus (Opsional)</label>
              <input
                v-model="quickSetForm.catatan"
                type="text"
                placeholder="Contoh: Javanese language, Arabic language, Bawa alat shalat..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between gap-2">
          <button
            v-if="quickSetForm.is_existing"
            type="button"
            @click="clearCurrentSlot"
            :disabled="savingQuickSet"
            class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer disabled:opacity-50"
          >
            Kosongkan Slot
          </button>
          <div v-else></div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="isQuickSetModalOpen = false"
              class="px-3.5 py-2 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              @click="saveQuickSet"
              :disabled="savingQuickSet || !quickSetForm.judul_kegiatan"
              class="inline-flex items-center gap-1.5 px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition disabled:opacity-50 cursor-pointer active:scale-95"
            >
              <svg v-if="savingQuickSet" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ savingQuickSet ? 'Menyimpan...' : 'Simpan Jadwal' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, defineComponent, h } from 'vue';
import api from '../../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError, showConfirm } from '../../services/swal';

const props = defineProps<{
  activeYearId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'create'): void;
  (e: 'edit', schedule: any): void;
}>();

const classes = ref<any[]>([]);
const schedules = ref<any[]>([]);
const timeSlots = ref<any[]>([]);
const teachersList = ref<any[]>([]);
const subjectsList = ref<any[]>([]);
const assignmentsList = ref<any[]>([]);

const selectedClassId = ref<number | null>(null);
const scheduleViewMode = ref<'matrix' | 'table'>('matrix');
const modeSlot2x35 = ref(true); // default true: gabungkan 2x35 jadi 1 baris
const tableScale = ref<'compact' | 'normal'>('compact'); // default compact: ramping fit layar tanpa scroll
const quickSetDurationMode = ref<'auto' | 'custom'>('auto');
const loadingSchedules = ref(false);
const loadingLookups = ref(false);

const calculatedDurationMinutes = computed(() => {
  if (!quickSetForm.value.jam_mulai || !quickSetForm.value.jam_selesai) return 0;
  const [h1, m1] = quickSetForm.value.jam_mulai.split(':').map(Number);
  const [h2, m2] = quickSetForm.value.jam_selesai.split(':').map(Number);
  if (isNaN(h1) || isNaN(m1) || isNaN(h2) || isNaN(m2)) return 0;
  let diff = (h2 * 60 + m2) - (h1 * 60 + m1);
  if (diff < 0) diff += 24 * 60;
  return diff;
});

const detectedJp = computed(() => {
  const m = calculatedDurationMinutes.value;
  if (m === 35) return 1;
  if (m === 70) return 2;
  if (m === 105) return 3;
  return 0; // custom
});

const calculatedDurationText = computed(() => {
  const m = calculatedDurationMinutes.value;
  if (m <= 0) return '-';
  const hours = Math.floor(m / 60);
  const mins = m % 60;
  if (hours > 0 && mins > 0) return `${hours} jam ${mins} menit (${m} mnt)`;
  if (hours > 0) return `${hours} jam (${m} mnt)`;
  return `${m} menit`;
});

const setJpDuration = (jp: number) => {
  quickSetDurationMode.value = 'auto';
  if (!quickSetForm.value.jam_mulai) {
    quickSetForm.value.jam_mulai = '08:00';
  }
  const [hStr, mStr] = quickSetForm.value.jam_mulai.split(':');
  let h = parseInt(hStr, 10);
  let m = parseInt(mStr, 10);
  if (isNaN(h) || isNaN(m)) {
    h = 8;
    m = 0;
  }
  const totalMin = h * 60 + m + (jp * 35);
  const endH = Math.floor(totalMin / 60) % 24;
  const endM = totalMin % 60;
  quickSetForm.value.jam_selesai = `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
};

const onJamMulaiChange = () => {
  if (detectedJp.value > 0) {
    setJpDuration(detectedJp.value);
  }
};

const selectedClass = computed(() => {
  return classes.value.find(c => c.id === selectedClassId.value) || classes.value[0];
});

const sortedSchedules = computed(() => {
  const dayOrder: Record<string, number> = { senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, sabtu: 6 };
  return [...schedules.value].sort((a, b) => {
    const da = dayOrder[a.hari] || 99;
    const db = dayOrder[b.hari] || 99;
    if (da !== db) return da - db;
    return (a.time_slot?.urutan || 0) - (b.time_slot?.urutan || 0);
  });
});

const loadLookups = async () => {
  loadingLookups.value = true;
  try {
    const [resClasses, resSlots, resTeachers, resSubjects, resAssigns] = await Promise.allSettled([
      api.get('/classes', { params: { all: true } }),
      api.get('/time-slots'),
      api.get('/teachers'),
      api.get('/subjects', { params: { all: true } }),
      api.get('/teacher-assignments', { params: { academic_year_id: props.activeYearId || undefined } }),
    ]);

    if (resClasses.status === 'fulfilled' && resClasses.value.data?.success) {
      classes.value = resClasses.value.data.data || [];
      if (!selectedClassId.value && classes.value.length > 0) {
        const cls3 = classes.value.find((c: any) => c.nama.includes('3'));
        selectedClassId.value = cls3 ? cls3.id : classes.value[0].id;
      }
    }
    if (resSlots.status === 'fulfilled' && resSlots.value.data?.success) {
      timeSlots.value = resSlots.value.data.data || [];
    }
    if (resTeachers.status === 'fulfilled' && resTeachers.value.data?.success) {
      teachersList.value = resTeachers.value.data.data || [];
    }
    if (resSubjects.status === 'fulfilled' && resSubjects.value.data?.success) {
      subjectsList.value = resSubjects.value.data.data || [];
    }
    if (resAssigns.status === 'fulfilled' && resAssigns.value.data?.success) {
      assignmentsList.value = resAssigns.value.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil lookup jadwal:', err);
  } finally {
    loadingLookups.value = false;
  }
};

const fetchClassSchedules = async () => {
  if (!selectedClassId.value) return;
  loadingSchedules.value = true;
  try {
    const params: any = {
      class_id: selectedClassId.value,
      all: true,
    };
    const academicYearId = props.activeYearId || selectedClass.value?.academic_year_id || 1;
    if (academicYearId) params.academic_year_id = academicYearId;

    const res = await api.get('/schedules', { params });
    if (res.data.success) {
      schedules.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil jadwal kelas', err);
  } finally {
    loadingSchedules.value = false;
  }
};

const selectClass = (clsId: number) => {
  selectedClassId.value = clsId;
  fetchClassSchedules();
};

const printSchedule = () => {
  window.print();
};

// Matrix Cell Helpers
const cellSchedule = (hari: string, urutan: number) => {
  return schedules.value.find(s => s.hari === hari && s.time_slot?.urutan === urutan);
};

const cellTitle = (hari: string, urutan: number, fallback: string = '') => {
  const s = cellSchedule(hari, urutan);
  if (s) {
    return s.judul_kegiatan || s.subject?.nama || fallback;
  }
  if (selectedClassId.value === 3 || schedules.value.length > 0) {
    return fallback;
  }
  return '';
};

const cellStyleClass = (hari: string, urutan: number) => {
  const s = cellSchedule(hari, urutan);
  if (!s && schedules.value.length === 0 && selectedClassId.value !== 3) {
    return 'bg-amber-50/20 hover:bg-purple-50 text-gray-400 font-normal';
  }
  const name = (s ? (s.judul_kegiatan || s.subject?.nama || '') : '').toUpperCase();

  if (name.includes('TEMA 1')) return 'bg-[#fca5a5] hover:bg-[#f87171] text-red-950 font-black';
  if (name.includes('TEMA 2')) return 'bg-[#7dd3fc] hover:bg-[#38bdf8] text-sky-950 font-black';
  if (name.includes('TEMA 3')) return 'bg-[#86efac] hover:bg-[#4ade80] text-emerald-950 font-black';
  if (name.includes('TEMA 4')) return 'bg-[#fed7aa] hover:bg-[#fb923c] text-amber-950 font-black';
  if (name.includes('TEMA 5')) return 'bg-[#e0e7ff] hover:bg-[#c7d2fe] text-indigo-950 font-black';

  if (s) {
    return 'bg-white hover:bg-gray-100 text-gray-900 font-bold';
  }
  return 'bg-white hover:bg-purple-50/50 text-gray-800 font-bold';
};

const cellTooltip = (hari: string, urutan: number) => {
  const s = cellSchedule(hari, urutan);
  if (s) {
    let tip = `${s.judul_kegiatan || s.subject?.nama} (${(s.tipe_kegiatan || 'kbm').toUpperCase()})`;
    if (s.teacher) tip += ` • Guru: ${s.teacher.nama_lengkap}`;
    if (s.catatan) tip += ` • Catatan: ${s.catatan}`;
    return tip;
  }
  return `Klik untuk mengatur slot ${hari} urutan ${urutan}`;
};

const formatSlotTime = (hari: string, urutan: number, fallback: string = '') => {
  const ts = timeSlots.value.find(t => t.hari === hari && t.urutan === urutan);
  if (modeSlot2x35.value) {
    if (urutan === 4) {
      if (ts?.jam_mulai && ts?.jam_selesai && ts.jam_selesai.slice(0, 5) !== '08:35') {
        return `${ts.jam_mulai.slice(0, 5)} - ${ts.jam_selesai.slice(0, 5)}`;
      }
      return hari === 'sabtu' ? '08.30 - 09.30' : '08.00 - 09.10';
    }
    if (urutan === 9) {
      if (ts?.jam_mulai && ts?.jam_selesai && ts.jam_selesai.slice(0, 5) !== '12:45') {
        return `${ts.jam_mulai.slice(0, 5)} - ${ts.jam_selesai.slice(0, 5)}`;
      }
      return '12.10 - 13.20';
    }
  }
  if (ts?.jam_mulai && ts?.jam_selesai) {
    return `${ts.jam_mulai.slice(0, 5)} - ${ts.jam_selesai.slice(0, 5)}`;
  }
  return fallback;
};

// Cell Component
const CellContent = defineComponent({
  props: {
    hari: { type: String, required: true },
    urutan: { type: Number, required: true },
    fallback: { type: String, default: '' },
  },
  setup(props) {
    return () => {
      const s = cellSchedule(props.hari, props.urutan);
      let title = '';
      if (s) {
        title = s.judul_kegiatan || s.subject?.nama || props.fallback;
      } else if (selectedClassId.value === 3 || (schedules.value.length > 0 && props.fallback)) {
        title = props.fallback;
      }

      if (!title) {
        return h(
          'div',
          { class: 'text-gray-300 hover:text-purple-600 font-bold text-[10px] py-1 flex items-center justify-center gap-1 transition' },
          [
            h('span', { class: 'text-xs leading-none font-bold' }, '+'),
            h('span', { class: 'text-[9px] font-medium leading-none' }, 'Atur')
          ]
        );
      }

      const teacher = s?.teacher?.nama_lengkap || '';
      const catatan = s?.catatan || '';

      const children: any[] = [
        h('div', { class: 'font-extrabold leading-tight text-[10.5px] sm:text-xs text-gray-900 tracking-tight' }, title)
      ];

      if (teacher) {
        children.push(
          h(
            'div',
            { class: 'text-[8.5px] font-bold text-purple-950 bg-black/5 hover:bg-black/10 px-1 py-0.5 rounded mt-0.5 truncate leading-tight transition inline-block max-w-full' },
            teacher
          )
        );
      }

      if (catatan) {
        children.push(
          h('div', { class: 'text-[8px] italic text-gray-700/90 truncate leading-tight mt-0.5' }, catatan)
        );
      }

      return h('div', { class: 'leading-tight py-0.5 select-none' }, children);
    };
  }
});

// Quick Set Modal
const isQuickSetModalOpen = ref(false);
const savingQuickSet = ref(false);
const quickSetForm = ref<any>({
  hari: '',
  slot_urutan: 1,
  time_slot_id: null,
  jam_mulai: '08:00',
  jam_selesai: '08:35',
  jam_label: '',
  judul_kegiatan: '',
  tipe_kegiatan: 'kbm',
  subject_id: null,
  teacher_id: null,
  catatan: '',
  is_existing: false,
});

const openQuickSet = (hari: string, urutan: number) => {
  const ts = timeSlots.value.find(t => t.hari === hari && t.urutan === urutan);
  const existing = cellSchedule(hari, urutan);

  let jamMulai = existing?.time_slot?.jam_mulai?.slice(0, 5) || ts?.jam_mulai?.slice(0, 5) || '08:00';
  let jamSelesai = existing?.time_slot?.jam_selesai?.slice(0, 5) || ts?.jam_selesai?.slice(0, 5) || '08:35';

  if (modeSlot2x35.value) {
    if (urutan === 4) {
      jamMulai = jamMulai || '08:00';
      if (jamSelesai === '08:35' || !existing?.time_slot?.jam_selesai) {
        jamSelesai = hari === 'sabtu' ? '09:30' : '09:10';
      }
    } else if (urutan === 9) {
      jamMulai = jamMulai || '12:10';
      if (jamSelesai === '12:45' || !existing?.time_slot?.jam_selesai) {
        jamSelesai = '13:20';
      }
    }
  }

  quickSetForm.value = {
    hari,
    slot_urutan: urutan,
    time_slot_id: ts?.id || existing?.time_slot_id || null,
    jam_mulai: jamMulai,
    jam_selesai: jamSelesai,
    jam_label: `${jamMulai} - ${jamSelesai}`,
    judul_kegiatan: existing?.judul_kegiatan || existing?.subject?.nama || (existing ? '' : cellTitle(hari, urutan, '')),
    tipe_kegiatan: existing?.tipe_kegiatan || 'kbm',
    subject_id: existing?.subject_id || null,
    teacher_id: existing?.teacher_id || null,
    catatan: existing?.catatan || '',
    is_existing: Boolean(existing),
  };

  if (!quickSetForm.value.teacher_id && selectedClass.value?.homeroom_teacher_id) {
    quickSetForm.value.teacher_id = selectedClass.value.homeroom_teacher_id;
  }

  isQuickSetModalOpen.value = true;
};

const findAssignedTeacherForSubject = (subId: number, slotUrutan: number = 1): number | null => {
  if (!subId || !selectedClassId.value) return null;

  const matches = assignmentsList.value.filter(
    (a: any) => a.subject_id === subId && a.class_id === selectedClassId.value
  );

  if (matches.length === 1) return matches[0].teacher_id;

  if (matches.length > 1) {
    if (slotUrutan <= 4) {
      const pagiMatch = matches.find((m: any) => m.keterangan && m.keterangan.toLowerCase().includes('pagi'));
      if (pagiMatch) return pagiMatch.teacher_id;
    } else {
      const siangMatch = matches.find((m: any) => m.keterangan && m.keterangan.toLowerCase().includes('siang'));
      if (siangMatch) return siangMatch.teacher_id;
    }
    return matches[0].teacher_id;
  }

  if (selectedClass.value?.homeroom_teacher_id) {
    return selectedClass.value.homeroom_teacher_id;
  }

  return null;
};

const onSubjectChange = () => {
  const subId = quickSetForm.value.subject_id;
  if (!subId) return;
  const sub = subjectsList.value.find(s => s.id === subId);
  if (!sub) return;

  quickSetForm.value.judul_kegiatan = sub.nama;
  quickSetForm.value.tipe_kegiatan = 'kbm';

  const assignedTeacherId = findAssignedTeacherForSubject(subId, quickSetForm.value.slot_urutan || 1);
  if (assignedTeacherId) {
    quickSetForm.value.teacher_id = assignedTeacherId;
  }
};

const applyPreset = (preset: string) => {
  quickSetForm.value.judul_kegiatan = preset;

  if (preset.startsWith('TEMA')) {
    quickSetForm.value.tipe_kegiatan = 'kbm';
    const num = parseInt(preset.replace(/\D/g, '')) || 1;
    const sub = subjectsList.value.find(s => s.is_tematik && (s.tema_nomor === num || s.kode === `TEMA${num}` || s.kode === `TEMA-${num}`));
    if (sub) {
      quickSetForm.value.subject_id = sub.id;
      const assignedTeacherId = findAssignedTeacherForSubject(sub.id, quickSetForm.value.slot_urutan || 1);
      if (assignedTeacherId) {
        quickSetForm.value.teacher_id = assignedTeacherId;
      }
    }
  } else if (['LENTERA', 'Upacara', 'Asmaul Husna', 'Dzikir+ Senam'].includes(preset)) {
    quickSetForm.value.tipe_kegiatan = 'pembiasaan';
    quickSetForm.value.subject_id = null;
    quickSetForm.value.teacher_id = null;
  } else if (['Shalat Dhuha + Istirahat', 'Shalat Dzuhur + Makan siang'].includes(preset)) {
    quickSetForm.value.tipe_kegiatan = 'istirahat';
    quickSetForm.value.subject_id = null;
    quickSetForm.value.teacher_id = null;
  } else if (preset.includes('Ekstrakulikuler')) {
    quickSetForm.value.tipe_kegiatan = 'ekskul';
    quickSetForm.value.subject_id = null;
  }
};

const saveQuickSet = async () => {
  if (!quickSetForm.value.time_slot_id) {
    showError('Gagal!', 'Data time slot waktu belum tersedia untuk sesi ini.');
    return;
  }

  savingQuickSet.value = true;
  try {
    const academicYearId = props.activeYearId || selectedClass.value?.academic_year_id || 1;
    const payload = {
      action: 'save',
      academic_year_id: academicYearId,
      class_id: selectedClassId.value,
      hari: quickSetForm.value.hari,
      time_slot_id: quickSetForm.value.time_slot_id,
      jam_mulai: quickSetForm.value.jam_mulai ? (quickSetForm.value.jam_mulai.length === 5 ? `${quickSetForm.value.jam_mulai}:00` : quickSetForm.value.jam_mulai) : null,
      jam_selesai: quickSetForm.value.jam_selesai ? (quickSetForm.value.jam_selesai.length === 5 ? `${quickSetForm.value.jam_selesai}:00` : quickSetForm.value.jam_selesai) : null,
      subject_id: quickSetForm.value.subject_id || null,
      teacher_id: quickSetForm.value.teacher_id || null,
      room_id: null,
      judul_kegiatan: quickSetForm.value.judul_kegiatan,
      tipe_kegiatan: quickSetForm.value.tipe_kegiatan || 'kbm',
      catatan: quickSetForm.value.catatan || null,
      force: true,
    };

    const res = await api.post('/schedules/quick-set', payload);
    if (res.data.success) {
      // If 2x35 mode is active, synchronize paired slot (slot 4 -> 5, slot 9 -> 10)
      const pairUrutan = quickSetForm.value.slot_urutan === 4 ? 5 : (quickSetForm.value.slot_urutan === 9 ? 10 : null);
      if (modeSlot2x35.value && pairUrutan) {
        const pairSlot = timeSlots.value.find(t => t.hari === quickSetForm.value.hari && t.urutan === pairUrutan);
        if (pairSlot) {
          try {
            await api.post('/schedules/quick-set', {
              ...payload,
              time_slot_id: pairSlot.id,
              jam_mulai: null,
              jam_selesai: null,
            });
          } catch (e) {
            // ignore
          }
        }
      }

      showSuccess('Berhasil!', 'Slot jadwal berhasil diperbarui.');
      isQuickSetModalOpen.value = false;
      await fetchClassSchedules();

      try {
        const resSlots = await api.get('/time-slots');
        if (resSlots.data.success) {
          timeSlots.value = resSlots.data.data;
        }
      } catch (e) {
        // ignore
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err.response?.data?.message || 'Terjadi kesalahan saat menyimpan jadwal.');
  } finally {
    savingQuickSet.value = false;
  }
};

const clearCurrentSlot = async () => {
  if (!quickSetForm.value.time_slot_id) return;

  savingQuickSet.value = true;
  try {
    const academicYearId = props.activeYearId || selectedClass.value?.academic_year_id || 1;
    const payload = {
      action: 'delete',
      academic_year_id: academicYearId,
      class_id: selectedClassId.value,
      hari: quickSetForm.value.hari,
      time_slot_id: quickSetForm.value.time_slot_id,
    };

    const res = await api.post('/schedules/quick-set', payload);
    if (res.data.success) {
      // Also clear paired slot if 2x35 mode
      const pairUrutan = quickSetForm.value.slot_urutan === 4 ? 5 : (quickSetForm.value.slot_urutan === 9 ? 10 : null);
      if (modeSlot2x35.value && pairUrutan) {
        const pairSlot = timeSlots.value.find(t => t.hari === quickSetForm.value.hari && t.urutan === pairUrutan);
        if (pairSlot) {
          try {
            await api.post('/schedules/quick-set', {
              ...payload,
              time_slot_id: pairSlot.id,
            });
          } catch (e) {
            // ignore
          }
        }
      }

      showSuccess('Dikosongkan!', 'Slot jadwal berhasil dikosongkan.');
      isQuickSetModalOpen.value = false;
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal mengosongkan slot.');
  } finally {
    savingQuickSet.value = false;
  }
};

const applyOfficialTemplateToClass = async () => {
  const cls = selectedClass.value;
  const name = cls?.nama || 'kelas terpilih';

  const confirmed = await showConfirm(
    'Terapkan Jadwal Resmi?',
    `Susunan jadwal standar (11 sesi x 6 hari seperti foto dinas) akan diterapkan pada ${name}. Jadwal yang sudah ada pada kelas ini akan diperbarui.`,
    'Ya, Terapkan Jadwal'
  );

  if (!confirmed) return;

  loadingSchedules.value = true;
  try {
    const academicYearId = props.activeYearId || selectedClass.value?.academic_year_id || 1;
    const res = await api.post('/schedules/apply-template', {
      class_id: selectedClassId.value,
      academic_year_id: academicYearId,
    });
    if (res.data.success) {
      showSuccess('Berhasil Diterapkan!', res.data.message);
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal Menerapkan Template!', err.response?.data?.message || 'Terjadi kesalahan saat menyalin template jadwal.');
  } finally {
    loadingSchedules.value = false;
  }
};

const clearEntireClassSchedules = async () => {
  const cls = selectedClass.value;
  const name = cls?.nama || 'kelas terpilih';

  const confirmed = await confirmDeleteWithKeyword(`seluruh jadwal ${name}`);
  if (!confirmed) return;

  loadingSchedules.value = true;
  try {
    const academicYearId = props.activeYearId || selectedClass.value?.academic_year_id || 1;
    const res = await api.post('/schedules/clear-class', {
      class_id: selectedClassId.value,
      academic_year_id: academicYearId,
    });
    if (res.data.success) {
      showSuccess('Berhasil Dibersihkan!', res.data.message);
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal Membersihkan!', err.response?.data?.message || 'Gagal membersihkan jadwal kelas.');
  } finally {
    loadingSchedules.value = false;
  }
};

const handleDeleteSchedule = async (sch: any) => {
  const confirmed = await confirmDeleteWithKeyword(`jadwal ${sch.hari} kelas ${sch.school_class?.nama || ''} mapel ${sch.subject?.nama || ''}`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/schedules/${sch.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Jadwal tidak dapat dihapus.');
  }
};

defineExpose({
  fetchClassSchedules,
  selectedClassId,
});

onMounted(async () => {
  await loadLookups();
  await fetchClassSchedules();
});
</script>
