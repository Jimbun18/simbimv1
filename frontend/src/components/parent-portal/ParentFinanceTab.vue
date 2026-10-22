<template>
  <div class="p-5 sm:p-7 space-y-6">
    <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
      <div>
        <h3 class="text-sm sm:text-base font-bold text-slate-900">Rincian Tagihan & Administrasi SPP</h3>
        <p class="text-xs text-slate-500">Pantau status pembayaran iuran sekolah dan SPP bulanan ananda secara transparan</p>
      </div>
      <span 
        class="px-3 py-1 rounded-full text-xs font-black uppercase inline-block w-fit"
        :class="financialSummary.status_tagihan === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
      >
        {{ financialSummary.status_tagihan === 'lunas' ? 'Semua Tagihan Lunas' : 'Ada Tagihan Aktif' }}
      </span>
    </div>

    <!-- Ringkasan Keuangan Box -->
    <div class="p-5 bg-gradient-to-r from-slate-50 to-emerald-50/30 rounded-3xl border border-slate-200/80 flex flex-col sm:flex-row justify-between gap-4">
      <div>
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tagihan Terdaftar</span>
        <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-1">
          Rp {{ formatRupiah(financialSummary.total_tagihan ?? 0) }}
        </div>
      </div>
      <div>
        <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Sudah Terbayar</span>
        <div class="text-xl sm:text-2xl font-black text-emerald-700 font-mono mt-1">
          Rp {{ formatRupiah(financialSummary.jumlah_terbayar ?? 0) }}
        </div>
      </div>
      <div>
        <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Sisa Belum Dibayar</span>
        <div class="text-xl sm:text-2xl font-black text-rose-600 font-mono mt-1">
          Rp {{ formatRupiah(financialSummary.sisa_tagihan ?? 0) }}
        </div>
      </div>
    </div>

    <!-- Rincian Daftar Invoice -->
    <div class="space-y-3">
      <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Tagihan Ananda:</h4>

      <div v-if="invoiceList.length > 0" class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
              <th class="py-2.5 px-3 rounded-l-xl">Jenis Iuran</th>
              <th class="py-2.5 px-3">Periode</th>
              <th class="py-2.5 px-3">Jatuh Tempo</th>
              <th class="py-2.5 px-3">Nominal</th>
              <th class="py-2.5 px-3">Terbayar</th>
              <th class="py-2.5 px-3">Sisa</th>
              <th class="py-2.5 px-3 rounded-r-xl">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="inv in invoiceList" :key="inv.id" class="hover:bg-slate-50/60 transition">
              <td class="py-3 px-3 font-bold text-slate-800">{{ inv.fee_type?.nama || 'Iuran SPP Sekolah' }}</td>
              <td class="py-3 px-3 font-mono text-slate-600">{{ inv.periode || '-' }}</td>
              <td class="py-3 px-3 font-mono text-slate-600">{{ formatDate(inv.jatuh_tempo) }}</td>
              <td class="py-3 px-3 font-mono font-bold text-slate-900">Rp {{ formatRupiah(inv.jumlah) }}</td>
              <td class="py-3 px-3 font-mono text-emerald-700 font-semibold">Rp {{ formatRupiah(inv.jumlah_dibayar) }}</td>
              <td class="py-3 px-3 font-mono text-rose-600 font-bold">Rp {{ formatRupiah(inv.sisa) }}</td>
              <td class="py-3 px-3">
                <span 
                  class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                  :class="inv.status === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ inv.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-center py-8 text-slate-400 text-xs bg-slate-50 rounded-2xl">
        Alhamdulillah, belum ada tagihan aktif untuk ananda.
      </div>
    </div>

    <!-- Petunjuk Pembayaran -->
    <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-200/80 text-xs text-slate-700 space-y-1.5">
      <div class="font-bold text-emerald-900 flex items-center gap-1.5">
        <span>💳 Petunjuk Pembayaran:</span>
      </div>
      <p class="leading-relaxed">
        Pembayaran SPP dapat dilakukan secara langsung di Loket Kasir Tata Usaha SDI Bina Insan Mandiri Purwokerto, atau melalui transfer ke rekening resmi sekolah (Bank Syariah Indonesia). Bukti transfer dapat dikonfirmasikan ke bagian keuangan.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { FinancialSummary, InvoiceItem } from './types';

defineProps<{
  financialSummary: FinancialSummary;
  invoiceList: InvoiceItem[];
}>();

const formatRupiah = (val: number | string | null | undefined) => {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val) || 0));
};

const formatDate = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return String(dateStr).substring(0, 10);
    return d.toLocaleDateString('id-ID', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return String(dateStr);
  }
};
</script>
