<script setup lang="ts">
import type { ReceiptData } from './types'
import { formatRupiah } from './types'

defineProps<{
  receiptData: ReceiptData
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const printReceipt = () => {
  window.print()
}
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <!-- Top Action Bar (Hidden on Print) -->
    <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <button 
        @click="emit('close')"
        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Selesai & Kembali</span>
      </button>

      <button 
        @click="printReceipt"
        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md transition flex items-center gap-2 cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span>Cetak Kuitansi Resmi (Ctrl + P)</span>
      </button>
    </div>

    <!-- OFFICIAL RECEIPT DOCUMENT SHEET -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-lg space-y-6 text-slate-800 print:shadow-none print:border-none print:p-0 print:m-0" id="official-receipt-sheet">
      <!-- Receipt Header -->
      <div class="border-b-2 border-dashed border-slate-300 pb-4 flex justify-between items-start">
        <div class="flex items-center gap-3 sm:gap-4">
          <img src="/logo.png" alt="Logo" class="w-14 h-14 sm:w-16 sm:h-16 object-contain" />
          <div>
            <h2 class="font-black text-slate-900 text-base sm:text-lg leading-tight uppercase">SDI BINA INSAN MANDIRI PURWOKERTO</h2>
            <p class="text-[11px] sm:text-xs text-slate-600">Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144</p>
            <p class="text-[10px] text-slate-400">NPSN: 70036695 • Telp: (0281) 635812</p>
          </div>
        </div>
        <div class="text-right">
          <span class="text-[10px] sm:text-xs font-black bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-md uppercase border border-emerald-200">
            BUKTI RESMI
          </span>
          <div class="font-mono text-xs sm:text-sm font-bold text-slate-800 mt-2">{{ receiptData.no_kuitansi }}</div>
        </div>
      </div>

      <!-- Receipt Body Details -->
      <div class="space-y-2.5 text-xs text-slate-700">
        <div class="flex justify-between py-1.5 border-b border-slate-100">
          <span class="text-slate-400">Telah terima dari:</span>
          <span class="font-black text-slate-900 text-sm uppercase">{{ receiptData.student_name }}</span>
        </div>
        <div class="flex justify-between py-1.5 border-b border-slate-100">
          <span class="text-slate-400">Nomor Induk Siswa (NIS):</span>
          <span class="font-mono font-bold text-slate-800">{{ receiptData.student_nis || '-' }}</span>
        </div>
        <div class="flex justify-between py-1.5 border-b border-slate-100">
          <span class="text-slate-400">Kelas / Rombel:</span>
          <span class="font-bold text-slate-800">{{ receiptData.class_name || '-' }}</span>
        </div>
        <div class="flex justify-between py-1.5 border-b border-slate-100">
          <span class="text-slate-400">Untuk Pembayaran:</span>
          <span class="font-bold text-slate-900">{{ receiptData.fee_name }} (Periode: {{ receiptData.period }})</span>
        </div>
        <div class="flex justify-between py-1.5 border-b border-slate-100">
          <span class="text-slate-400">Metode Bayar:</span>
          <span class="uppercase font-bold text-slate-800">{{ receiptData.metode }}</span>
        </div>
        <div class="flex justify-between py-1.5 border-b border-slate-100">
          <span class="text-slate-400">Tanggal Pembayaran:</span>
          <span class="font-mono text-slate-800">{{ receiptData.tanggal }}</span>
        </div>

        <!-- Total Box -->
        <div class="bg-emerald-50/70 p-5 rounded-2xl mt-4 border border-emerald-200">
          <div class="flex justify-between items-center">
            <span class="font-bold text-emerald-950 text-xs sm:text-sm uppercase tracking-wider">JUMLAH DIBAYAR:</span>
            <span class="font-mono text-xl sm:text-2xl font-black text-emerald-700">{{ formatRupiah(receiptData.jumlah) }}</span>
          </div>
          <div class="text-xs italic text-slate-600 mt-2">
            <strong>Terbilang:</strong> {{ receiptData.terbilang }} Rupiah.
          </div>
          <div v-if="receiptData.sisa_tagihan > 0" class="mt-2 pt-2 border-t border-emerald-200/60 text-xs text-rose-700 font-semibold flex justify-between">
            <span>Sisa Tagihan yang Belum Dibayar:</span>
            <span class="font-mono font-bold">{{ formatRupiah(receiptData.sisa_tagihan) }}</span>
          </div>
          <div v-else class="mt-2 pt-2 border-t border-emerald-200/60 text-xs text-emerald-800 font-bold flex justify-between">
            <span>Status Tagihan:</span>
            <span>LUNAS ✓</span>
          </div>
        </div>
      </div>

      <!-- Receipt Signatures -->
      <div class="grid grid-cols-2 text-center text-xs pt-4 text-slate-700">
        <div>
          <p class="text-slate-500">Penyetor / Wali Murid</p>
          <div class="h-16"></div>
          <p class="font-bold underline text-slate-800">( ........................................ )</p>
        </div>
        <div>
          <p class="text-slate-500">Purwokerto, {{ receiptData.tanggal }}</p>
          <p class="font-semibold text-slate-700">Kasir / Bagian Keuangan</p>
          <div class="h-16 flex items-center justify-center font-serif text-emerald-700 italic text-xs font-bold">
            [ LUNAS SDI BIM ]
          </div>
          <p class="font-bold underline text-slate-900">{{ receiptData.recorded_by || 'Bendahara Sekolah' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  @page {
    size: auto;
    margin: 10mm;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
