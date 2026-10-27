import type { Directive, DirectiveBinding } from 'vue'

interface RevealOptions {
  type?: 'fade-up' | 'fade-in' | 'scale' | 'slide-left' | 'slide-right'
  delay?: number
  duration?: number
  threshold?: number
}

// Global IntersectionObserver instance for high performance
let revealObserver: IntersectionObserver | null = null

function getObserver(): IntersectionObserver {
  if (!revealObserver && typeof window !== 'undefined') {
    revealObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const el = entry.target as HTMLElement
            el.classList.add('is-revealed')
            // Once revealed, unobserve to free resources & prevent replay
            observer.unobserve(el)
          }
        })
      },
      {
        root: null,
        rootMargin: '0px 0px -60px 0px',
        threshold: 0.12,
      }
    )
  }
  return revealObserver!
}

export const vReveal: Directive<HTMLElement, string | RevealOptions | undefined> = {
  mounted(el, binding: DirectiveBinding) {
    if (typeof window === 'undefined') return

    // Check user preference for reduced motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (prefersReducedMotion) {
      el.classList.add('is-revealed')
      return
    }

    el.classList.add('reveal-base')

    let type = 'fade-up'
    let delay = 0

    if (typeof binding.value === 'string') {
      type = binding.value
    } else if (typeof binding.value === 'object' && binding.value !== null) {
      if (binding.value.type) type = binding.value.type
      if (binding.value.delay) delay = binding.value.delay
    }

    // Check binding modifiers (e.g. v-reveal.scale, v-reveal.delay200)
    if (binding.modifiers) {
      if (binding.modifiers['scale']) type = 'scale'
      if (binding.modifiers['fade-in']) type = 'fade-in'
      if (binding.modifiers['slide-left']) type = 'slide-left'
      if (binding.modifiers['slide-right']) type = 'slide-right'

      if (binding.modifiers['delay100']) delay = 100
      if (binding.modifiers['delay200']) delay = 200
      if (binding.modifiers['delay300']) delay = 300
      if (binding.modifiers['delay400']) delay = 400
    }

    el.classList.add(`reveal-${type}`)
    if (delay > 0) {
      el.style.transitionDelay = `${delay}ms`
    }

    const observer = getObserver()
    observer.observe(el)
  },
  unmounted(el) {
    if (revealObserver) {
      revealObserver.unobserve(el)
    }
  }
}

/**
 * Automatically observe all elements in document with class .reveal-on-scroll or .reveal-item
 */
export function initAutoScrollReveal() {
  if (typeof window === 'undefined') return

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (prefersReducedMotion) return

  const observer = getObserver()

  const observeElements = () => {
    const targets = document.querySelectorAll<HTMLElement>(
      '.reveal-on-scroll, .reveal-fade-up, .reveal-scale, .reveal-slide-left, .reveal-slide-right'
    )
    targets.forEach((target) => {
      if (!target.classList.contains('is-revealed') && !target.classList.contains('reveal-base')) {
        target.classList.add('reveal-base')
        observer.observe(target)
      }
    })
  }

  // Observe current elements
  observeElements()

  // Use MutationObserver to catch dynamically mounted pages & cards
  const mutationObserver = new MutationObserver(() => {
    observeElements()
  })

  mutationObserver.observe(document.body, {
    childList: true,
    subtree: true,
  })
}
