<script setup lang="ts">
import { ref, onBeforeUnmount, nextTick } from 'vue';

const props = withDefaults(
  defineProps<{
    text?: string;
    position?: 'top' | 'bottom' | 'auto';
    size?: 'xs' | 'sm' | 'md';
  }>(),
  {
    text: '',
    position: 'auto',
    size: 'sm',
  }
);

const triggerRef = ref<HTMLElement | null>(null);
const isVisible = ref(false);
const coords = ref({ top: 0, left: 0, placeAbove: false });

const updatePosition = () => {
  if (!triggerRef.value) return;
  const rect = triggerRef.value.getBoundingClientRect();
  
  // Deteksi penempatan: jika posisi auto atau bottom
  let placeAbove = props.position === 'top';
  if (props.position === 'auto') {
    // Jika terlalu dekat ke bawah layar (< 140px), tempatkan di atas
    placeAbove = rect.bottom + 140 > window.innerHeight;
  }

  const top = placeAbove ? rect.top - 8 : rect.bottom + 8;

  // Lebar tooltip ~256px (w-64)
  const tooltipWidth = 256;
  let left = rect.left;

  // Cegah terpotong di tepi kanan layar
  if (left + tooltipWidth > window.innerWidth - 12) {
    left = Math.max(12, window.innerWidth - tooltipWidth - 12);
  }
  if (left < 12) {
    left = 12;
  }

  coords.value = { top, left, placeAbove };
};

const show = () => {
  updatePosition();
  isVisible.value = true;
  window.addEventListener('scroll', handleScroll, { passive: true });
  window.addEventListener('resize', handleScroll, { passive: true });
};

const hide = () => {
  isVisible.value = false;
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', handleScroll);
};

const toggle = () => {
  if (isVisible.value) {
    hide();
  } else {
    show();
  }
};

const handleScroll = () => {
  if (isVisible.value) {
    // Sembunyikan saat pengguna scroll tabel atau halaman agar tidak melayang lepas
    hide();
  }
};

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', handleScroll);
});
</script>

<template>
  <div 
    ref="triggerRef"
    class="relative inline-flex items-center group focus:outline-none select-none align-middle"
    @mouseenter="show"
    @mouseleave="hide"
    @click.stop="toggle"
    tabindex="0"
    role="button"
    :aria-label="text || 'Bantuan informasi'"
  >
    <!-- Ikon Tombol '?' Bulat Jelas, Tegas, & Elegan -->
    <span
      class="inline-flex items-center justify-center rounded-full font-bold transition-all duration-150 cursor-pointer shadow-2xs shrink-0"
      :class="[
        size === 'xs' ? 'w-4 h-4 text-[10px]' : size === 'md' ? 'w-5 h-5 text-xs' : 'w-4 h-4 text-[10px]',
        isVisible 
          ? 'bg-emerald-600 text-white border border-emerald-600 ring-2 ring-emerald-300 scale-110 shadow-sm' 
          : 'bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 hover:scale-105'
      ]"
    >
      ?
    </span>

    <!-- Teleport ke <body> agar 100% bebas dari overflow-hidden tabel/card & stacking context -->
    <Teleport to="body">
      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="isVisible"
          class="fixed z-[99999] w-64 max-w-xs px-3 py-2 text-[11px] font-normal leading-relaxed text-white bg-slate-900/95 backdrop-blur-xs rounded-xl shadow-2xl pointer-events-none select-none border border-slate-700/60"
          :style="{
            top: `${coords.top}px`,
            left: `${coords.left}px`,
            transform: coords.placeAbove ? 'translateY(-100%)' : 'none'
          }"
        >
          <slot>
            <span class="block whitespace-normal break-words">{{ text }}</span>
          </slot>
          
          <!-- Arrow Pointer Kecil -->
          <span
            class="absolute w-2 h-2 bg-slate-900/95 rotate-45 border-slate-700/60"
            :class="[
              coords.placeAbove 
                ? 'top-full -translate-y-1 border-r border-b left-3' 
                : 'bottom-full translate-y-1 border-l border-t left-3'
            ]"
          ></span>
        </div>
      </transition>
    </Teleport>
  </div>
</template>