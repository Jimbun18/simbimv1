import type { Directive, DirectiveBinding } from 'vue'

/**
 * Trigger liquid water droplet splash animation at click position
 */
export function triggerSplash(
  event: MouseEvent | TouchEvent, 
  targetElement?: HTMLElement, 
  variant: 'light' | 'emerald' | 'amber' | 'dark' = 'light'
) {
  const target = targetElement || (event.currentTarget as HTMLElement)
  if (!target) return

  // Ensure button has relative positioning and hidden overflow for containing the splash
  const computedStyle = window.getComputedStyle(target)
  if (computedStyle.position === 'static') {
    target.style.position = 'relative'
  }
  target.style.overflow = 'hidden'

  const rect = target.getBoundingClientRect()
  
  // Extract coordinate
  let clientX = 0
  let clientY = 0
  if ('touches' in event && event.touches.length > 0) {
    clientX = event.touches[0].clientX
    clientY = event.touches[0].clientY
  } else if ('clientX' in event) {
    clientX = (event as MouseEvent).clientX
    clientY = (event as MouseEvent).clientY
  } else {
    clientX = rect.left + rect.width / 2
    clientY = rect.top + rect.height / 2
  }

  const x = clientX - rect.left
  const y = clientY - rect.top

  // Radius needs to cover the farthest corner of the button
  const diameter = Math.max(rect.width, rect.height) * 2.5

  // Container for splash ripples & droplets
  const splashContainer = document.createElement('span')
  splashContainer.className = 'splash-container'
  splashContainer.style.position = 'absolute'
  splashContainer.style.inset = '0'
  splashContainer.style.pointerEvents = 'none'
  splashContainer.style.overflow = 'hidden'
  splashContainer.style.borderRadius = computedStyle.borderRadius || 'inherit'
  splashContainer.style.zIndex = '30'

  // Determine ripple color tone
  let waveGradient = 'radial-gradient(circle, rgba(255, 255, 255, 0.75) 0%, rgba(255, 255, 255, 0.3) 45%, rgba(255, 255, 255, 0) 70%)'
  let secondaryGradient = 'radial-gradient(circle, rgba(255, 255, 255, 0.5) 0%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0) 75%)'
  let dropletColor = 'rgba(255, 255, 255, 0.9)'

  if (variant === 'amber') {
    waveGradient = 'radial-gradient(circle, rgba(251, 191, 36, 0.8) 0%, rgba(245, 158, 11, 0.4) 45%, rgba(245, 158, 11, 0) 70%)'
    secondaryGradient = 'radial-gradient(circle, rgba(253, 230, 138, 0.6) 0%, rgba(251, 191, 36, 0.2) 50%, rgba(251, 191, 36, 0) 75%)'
    dropletColor = '#f59e0b'
  } else if (variant === 'emerald') {
    waveGradient = 'radial-gradient(circle, rgba(52, 211, 153, 0.8) 0%, rgba(16, 185, 129, 0.4) 45%, rgba(16, 185, 129, 0) 70%)'
    secondaryGradient = 'radial-gradient(circle, rgba(167, 243, 208, 0.6) 0%, rgba(52, 211, 153, 0.2) 50%, rgba(52, 211, 153, 0) 75%)'
    dropletColor = '#10b981'
  } else if (variant === 'dark') {
    waveGradient = 'radial-gradient(circle, rgba(15, 23, 42, 0.35) 0%, rgba(15, 23, 42, 0.15) 50%, rgba(15, 23, 42, 0) 70%)'
    secondaryGradient = 'radial-gradient(circle, rgba(30, 41, 59, 0.25) 0%, rgba(30, 41, 59, 0.1) 50%, rgba(30, 41, 59, 0) 75%)'
    dropletColor = 'rgba(15, 23, 42, 0.4)'
  }

  // 1. Primary Splash Wave (Central ripple expanding fast)
  const primaryWave = document.createElement('span')
  primaryWave.className = 'splash-wave-primary'
  primaryWave.style.position = 'absolute'
  primaryWave.style.left = `${x}px`
  primaryWave.style.top = `${y}px`
  primaryWave.style.width = `${diameter}px`
  primaryWave.style.height = `${diameter}px`
  primaryWave.style.transform = 'translate(-50%, -50%) scale(0)'
  primaryWave.style.borderRadius = '50%'
  primaryWave.style.background = waveGradient
  primaryWave.style.animation = 'splashWaveExpand 650ms cubic-bezier(0.2, 0.8, 0.2, 1) forwards'

  // 2. Secondary Echo Wave (Delayed ripple following primary wave)
  const secondaryWave = document.createElement('span')
  secondaryWave.className = 'splash-wave-secondary'
  secondaryWave.style.position = 'absolute'
  secondaryWave.style.left = `${x}px`
  secondaryWave.style.top = `${y}px`
  secondaryWave.style.width = `${diameter * 0.75}px`
  secondaryWave.style.height = `${diameter * 0.75}px`
  secondaryWave.style.transform = 'translate(-50%, -50%) scale(0)'
  secondaryWave.style.borderRadius = '50%'
  secondaryWave.style.background = secondaryGradient
  secondaryWave.style.animation = 'splashWaveExpand 750ms cubic-bezier(0.2, 0.8, 0.2, 1) 90ms forwards'

  splashContainer.appendChild(primaryWave)
  splashContainer.appendChild(secondaryWave)

  // 3. Mini Water Droplets Splash Particles (4-6 radiating droplets)
  const dropletCount = 6
  for (let i = 0; i < dropletCount; i++) {
    const angle = (i * (360 / dropletCount) + (Math.random() * 30 - 15)) * (Math.PI / 180)
    const distance = 25 + Math.random() * 45
    const dropletSize = 4 + Math.random() * 4

    const dx = Math.cos(angle) * distance
    const dy = Math.sin(angle) * distance

    const droplet = document.createElement('span')
    droplet.className = 'splash-droplet'
    droplet.style.position = 'absolute'
    droplet.style.left = `${x}px`
    droplet.style.top = `${y}px`
    droplet.style.width = `${dropletSize}px`
    droplet.style.height = `${dropletSize}px`
    droplet.style.borderRadius = '50%'
    droplet.style.backgroundColor = dropletColor
    droplet.style.setProperty('--dx', `${dx}px`)
    droplet.style.setProperty('--dy', `${dy}px`)
    droplet.style.animation = `splashDropletFly 550ms cubic-bezier(0.16, 1, 0.3, 1) ${Math.random() * 40}ms forwards`

    splashContainer.appendChild(droplet)
  }

  target.appendChild(splashContainer)

  // Remove element after animation finishes
  setTimeout(() => {
    if (splashContainer.parentNode === target) {
      target.removeChild(splashContainer)
    }
  }, 900)
}

/**
 * Vue Directive: v-splash
 * Usage:
 *   <button v-splash>Click Me</button>
 *   <button v-splash="'amber'">Amber Splash</button>
 *   <button v-splash="'emerald'">Emerald Splash</button>
 *   <button v-splash="'dark'">Dark Splash</button>
 */
export const vSplash: Directive<HTMLElement, 'light' | 'emerald' | 'amber' | 'dark' | undefined> = {
  mounted(el, binding: DirectiveBinding) {
    const handler = (e: MouseEvent) => {
      const variant = binding.value || (
        el.classList.contains('bg-white') || el.classList.contains('bg-slate-50')
          ? 'dark'
          : el.classList.contains('from-amber-400') || el.classList.contains('bg-amber-400') || el.classList.contains('bg-amber-500')
          ? 'amber'
          : 'light'
      )
      triggerSplash(e, el, variant)
    }

    el.addEventListener('click', handler)
    ;(el as any)._splashHandler = handler
  },
  unmounted(el) {
    if ((el as any)._splashHandler) {
      el.removeEventListener('click', (el as any)._splashHandler)
      delete (el as any)._splashHandler
    }
  }
}

/**
 * Global click delegate to automatically trigger splash on any button or .btn-splash
 */
export function initGlobalSplash() {
  if (typeof window === 'undefined') return

  document.addEventListener('click', (e) => {
    const target = (e.target as HTMLElement)?.closest('button, .btn-splash, [data-splash]') as HTMLElement | null
    if (!target) return
    // If it already has v-splash, don't duplicate
    if ((target as any)._splashHandler) return

    // Auto variant detection based on class
    let variant: 'light' | 'emerald' | 'amber' | 'dark' = 'light'
    const classList = target.className || ''
    if (classList.includes('bg-white') || classList.includes('bg-slate-50') || classList.includes('bg-emerald-50') || classList.includes('bg-amber-50')) {
      variant = 'dark'
    } else if (classList.includes('amber')) {
      variant = 'amber'
    } else if (classList.includes('emerald') || classList.includes('teal')) {
      variant = 'light'
    }

    triggerSplash(e, target, variant)
  }, { passive: true })
}
