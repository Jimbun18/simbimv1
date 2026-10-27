<script setup lang="ts">
import { ref } from 'vue'

const isOpen = ref(false)

const openChat = (phone: string, role: string) => {
  const msg = encodeURIComponent(`Bismillah, Assalamu'alaikum ${role} SDI Bina Insan Mandiri Purwokerto, saya ingin menanyakan informasi mengenai sekolah/SPMB.`)
  window.open(`https://wa.me/${phone}?text=${msg}`, '_blank')
}
</script>

<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end print:hidden">
    <!-- Popover Card Pilihan Kontak -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform opacity-0 scale-95 translate-y-2"
      enter-to-class="transform opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform opacity-100 scale-100 translate-y-0"
      leave-to-class="transform opacity-0 scale-95 translate-y-2"
    >
      <div 
        v-if="isOpen" 
        class="mb-3 w-72 sm:w-80 bg-white rounded-3xl shadow-2xl border border-emerald-100 overflow-hidden"
      >
        <!-- Header Popup -->
        <div class="bg-gradient-to-r from-emerald-800 to-teal-900 p-4 text-white relative">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-lg">
                🏫
              </div>
              <div>
                <h4 class="font-black text-sm text-white leading-tight">Hubungi SDI BIM</h4>
                <p class="text-[10px] text-emerald-200">SDI Bina Insan Mandiri Purwokerto</p>
              </div>
            </div>
            <button 
              @click="isOpen = false"
              class="text-white/70 hover:text-white p-1 rounded-full hover:bg-white/10 transition cursor-pointer"
              aria-label="Tutup"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <p class="text-[11px] text-emerald-100/90 mt-2.5 leading-relaxed">
            Silakan hubungi layanan resmi kami via WhatsApp:
          </p>
        </div>

        <!-- Opsi Pilihan Chat -->
        <div class="p-3 space-y-2 bg-slate-50">
          <!-- Pilihan 1: Sekretariat Sekolah -->
          <button
            @click="openChat('6285138568694', 'Sekretariat')"
            v-splash="'emerald'"
            class="btn-splash w-full text-left p-3 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition-all flex items-center gap-3 cursor-pointer group shadow-xs active:scale-95"
          >
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 text-lg group-hover:scale-105 transition">
              🏛️
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-bold text-xs text-slate-900 flex items-center justify-between">
                <span>Sekretariat Sekolah</span>
                <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Resmi</span>
              </div>
              <div class="text-[11px] text-emerald-700 font-mono font-semibold">0851-3856-8694</div>
              <div class="text-[10px] text-slate-500 truncate">Informasi umum & kelembagaan</div>
            </div>
          </button>

          <!-- Pilihan 2: Panitia SPMB -->
          <button
            @click="openChat('6285150725032', 'Panitia SPMB')"
            v-splash="'amber'"
            class="btn-splash w-full text-left p-3 rounded-2xl bg-white hover:bg-amber-50 border border-slate-200 hover:border-amber-300 transition-all flex items-center gap-3 cursor-pointer group shadow-xs active:scale-95"
          >
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center shrink-0 text-lg group-hover:scale-105 transition">
              📝
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-bold text-xs text-slate-900 flex items-center justify-between">
                <span>Panitia SPMB</span>
                <span class="text-[9px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold">Pendaftaran</span>
              </div>
              <div class="text-[11px] text-amber-800 font-mono font-semibold">0851-5072-5032</div>
              <div class="text-[10px] text-slate-500 truncate">Konsultasi pendaftaran murid baru</div>
            </div>
          </button>
        </div>

        <div class="p-2.5 bg-white border-t border-slate-100 text-center text-[10px] text-slate-400">
          Senin – Sabtu • 07.30 – 14.30 WIB
        </div>
      </div>
    </transition>

    <!-- Floating Toggle Button with Splash Animation -->
    <button
      @click="isOpen = !isOpen"
      v-splash="'emerald'"
      class="btn-splash group flex items-center gap-2.5 px-4 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xl shadow-emerald-700/30 hover:shadow-2xl hover:scale-105 transition duration-200 cursor-pointer border border-emerald-400/40 relative active:scale-95"
      aria-label="Chat WhatsApp SDI BIM"
    >
      <!-- Pulse Effect -->
      <span class="absolute -top-1 -right-1 flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-400"></span>
      </span>

      <!-- WhatsApp SVG Icon -->
      <svg class="w-5 h-5 text-white shrink-0" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.668-.699c.971.532 1.77.82 2.792.82 3.183 0 5.768-2.587 5.768-5.766.001-3.18-2.585-5.766-5.768-5.766zm9.969 5.828c0 5.518-4.482 10-10 10-1.748 0-3.385-.45-4.819-1.238l-7.181 1.882 1.916-6.993c-.886-1.488-1.396-3.23-1.396-5.091 0-5.518 4.482-10 10-10 5.518 0 10 4.482 10 10z"/>
      </svg>
      <span class="hidden sm:inline">Hubungi Kami</span>
    </button>
  </div>
</template>
