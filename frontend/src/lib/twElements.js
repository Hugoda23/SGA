import {
  Ripple,
  Button,
  Collapse,
  Dropdown,
  Modal,
  Offcanvas,
  Popover,
  Tab,
  Tooltip,
} from 'tw-elements'

const RIPPLE_SELECTOR = '[data-twe-ripple-init]'

function isDarkBackground(color) {
  const match = color.match(/\d+(\.\d+)?/g)
  if (!match || match.length < 3) return false
  const [r, g, b] = match.slice(0, 3).map(Number)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance < 0.5
}

function setupRippleColor(el) {
  if (!el.hasAttribute('data-twe-ripple-color')) {
    el.setAttribute(
      'data-twe-ripple-color',
      isDarkBackground(window.getComputedStyle(el).backgroundColor)
        ? 'light'
        : 'dark'
    )
  }
}

/* ------------------------------------------------------------------ */
/* Ripple: per-element init with MutationObserver (React re-renders)   */
/* ------------------------------------------------------------------ */

function initRipples(node) {
  if (!node || node.nodeType !== 1) return
  if (node.matches?.(RIPPLE_SELECTOR) && !Ripple.getInstance(node)) {
    setupRippleColor(node)
    new Ripple(node)
  }
  node.querySelectorAll?.(RIPPLE_SELECTOR).forEach((el) => {
    if (!Ripple.getInstance(el)) {
      setupRippleColor(el)
      new Ripple(el)
    }
  })
}

let observer = null

/* ------------------------------------------------------------------ */
/* Delegated togglers (initTWE only scans the DOM once, so we bind     */
/* our own persistent document listeners that survive React routes)    */
/* ------------------------------------------------------------------ */

const delegated = new Set()
let currentModal = null

function registerDelegation() {
  if (delegated.has('all')) return
  delegated.add('all')

  document.addEventListener('click', (event) => {
    if (!event.target?.closest) return
    const target = event.target

    /* -------- Button -------- */
    if (target.closest("[data-twe-toggle='button']")) {
      Button.getOrCreateInstance(target.closest("[data-twe-toggle='button']")).toggle()
      return
    }

    /* -------- Collapse -------- */
    const collapseToggler = target.closest('[data-twe-collapse-init]')
    if (collapseToggler) {
      const selector =
        collapseToggler.getAttribute('data-twe-target') ||
        collapseToggler.getAttribute('href')
      const elements = document.querySelectorAll(selector)
      elements.forEach((el) =>
        Collapse.getOrCreateInstance(el, { toggle: false }).toggle()
      )
      return
    }

    /* -------- Tab -------- */
    const tabToggler = target.closest(
      "[data-twe-toggle='tab'], [data-twe-toggle='pill'], [data-twe-toggle='list']"
    )
    if (tabToggler) {
      if (['A', 'AREA'].includes(tabToggler.tagName)) event.preventDefault()
      if (tabToggler.disabled) return
      Tab.getOrCreateInstance(tabToggler).show()
      return
    }

    /* -------- Dropdown -------- */
    const dropdownToggler = target.closest('[data-twe-dropdown-toggle-ref]')
    if (dropdownToggler) {
      event.preventDefault()
      Dropdown.getOrCreateInstance(dropdownToggler).toggle()
      return
    }

    /* -------- Modal / Offcanvas -------- */
    const modalToggler = target.closest("[data-twe-toggle='modal']")
    if (modalToggler) {
      event.preventDefault()
      if (['A', 'AREA'].includes(modalToggler.tagName)) event.preventDefault()
      const selector =
        modalToggler.getAttribute('data-twe-target') ||
        modalToggler.getAttribute('href')
      const modalEl = document.querySelector(selector)
      if (!modalEl) return
      if (currentModal && currentModal !== modalEl) {
        Modal.getInstance(currentModal)?.hide()
      }
      Modal.getOrCreateInstance(modalEl).toggle()
      if (currentModal !== modalEl) currentModal = modalEl
      return
    }

    const offcanvasToggler = target.closest('[data-twe-offcanvas-toggle]')
    if (offcanvasToggler) {
      event.preventDefault()
      const selector =
        offcanvasToggler.getAttribute('data-twe-target') ||
        offcanvasToggler.getAttribute('href')
      const offcanvasEl = document.querySelector(selector)
      if (!offcanvasEl) return
      Offcanvas.getOrCreateInstance(offcanvasEl).toggle(offcanvasToggler)
      return
    }
  })
}

function initStatic() {
  ;['[data-twe-toggle="tooltip"]', '[data-twe-toggle="popover"]'].forEach(
    (selector) => {
      document.querySelectorAll(selector).forEach((el) => {
        const Cls = selector.includes('tooltip') ? Tooltip : Popover
        if (!Cls.getInstance(el)) new Cls(el)
      })
    }
  )
}

/* ------------------------------------------------------------------ */

export function initTwElements() {
  registerDelegation()
  if (!observer) {
    observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          initRipples(node)
        })
      })
    })
    observer.observe(document.body, { childList: true, subtree: true })
  }
  initRipples(document)
  initStatic()
}
