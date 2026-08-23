import { useEffect } from 'react'

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
  '3xl': 'max-w-3xl',
  '4xl': 'max-w-4xl',
  '5xl': 'max-w-5xl',
}

export default function Modal({ open, onClose, title, children, footer, size = 'md', scrollable }) {
  useEffect(() => {
    if (!open) return
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.body.style.overflow = prev
    }
  }, [open])

  useEffect(() => {
    if (!open) return
    const onKey = (e) => {
      if (e.key === 'Escape') onClose?.()
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  return (
    <div
      className="fixed inset-0 z-[1055] overflow-y-auto overflow-x-hidden outline-none"
      tabIndex={-1}
    >
      <div className="pointer-events-none relative flex min-h-full w-full items-center justify-center p-4">
        <div className="pointer-events-auto fixed inset-0 bg-black/50" onClick={onClose} />
        <div
          className={`pointer-events-auto relative flex w-full flex-col rounded-xl border-none bg-white bg-clip-padding text-current shadow-4 outline-none dark:bg-surface-dark ${sizeClasses[size]}`}
        >
          <div className="flex flex-shrink-0 items-center justify-between rounded-t-xl border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
            <h5 className="text-xl font-medium leading-normal text-neutral-800 dark:text-neutral-200">
              {title}
            </h5>
            <button
              type="button"
              onClick={onClose}
              aria-label="Cerrar"
              className="box-content rounded-none border-none text-neutral-500 hover:text-neutral-700 hover:no-underline hover:opacity-75 focus:opacity-100 focus:shadow-none focus:outline-none dark:text-neutral-300 dark:hover:text-neutral-100"
            >
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div className={`relative p-4 ${scrollable ? 'max-h-[70vh] overflow-y-auto' : ''}`}>
            {children}
          </div>
          {footer && (
            <div className="flex flex-shrink-0 flex-wrap items-center justify-end gap-2 rounded-b-xl border-t-2 border-neutral-100 p-4 dark:border-neutral-600">
              {footer}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
