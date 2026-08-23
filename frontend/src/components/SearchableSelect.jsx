import { useState, useEffect, useRef, useMemo } from 'react'
import { input } from '../lib/twClasses'

export default function SearchableSelect({ label, name, value, onChange, required, options = [], placeholder = 'Buscar...', emptyMessage = 'Sin resultados' }) {
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [highlighted, setHighlighted] = useState(0)
  const rootRef = useRef(null)
  const inputRef = useRef(null)

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return options
    return options.filter((opt) => opt.label.toLowerCase().includes(q))
  }, [options, query])

  const selected = options.find((opt) => String(opt.value) === String(value))

  useEffect(() => {
    const onClick = (e) => {
      if (rootRef.current && !rootRef.current.contains(e.target)) setOpen(false)
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  useEffect(() => {
    if (!open) setQuery('')
  }, [open])

  const selectOption = (opt) => {
    onChange?.({ target: { name, value: opt.value } })
    setOpen(false)
  }

  const handleKeyDown = (e) => {
    if (!open) return
    if (e.key === 'Escape') {
      e.preventDefault()
      setOpen(false)
    } else if (e.key === 'ArrowDown') {
      e.preventDefault()
      setHighlighted((h) => Math.min(h + 1, filtered.length - 1))
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setHighlighted((h) => Math.max(h - 1, 0))
    } else if (e.key === 'Enter') {
      e.preventDefault()
      if (filtered[highlighted]) selectOption(filtered[highlighted])
    }
  }

  return (
    <div ref={rootRef} className="relative">
      <label className={input.label}>{label}{required && <span className="ml-1 text-danger">*</span>}</label>
      <input
        ref={inputRef}
        type="text"
        value={open ? query : selected?.label || ''}
        readOnly={!open}
        onFocus={() => { setOpen(true); setHighlighted(0) }}
        onChange={(e) => { setQuery(e.target.value); setHighlighted(0) }}
        onKeyDown={handleKeyDown}
        placeholder={open ? placeholder : 'Seleccionar...'}
        className={`${input.base} cursor-pointer`}
        aria-label={label}
      />
      <svg
        className={`pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400 transition-transform ${open ? 'rotate-180' : ''}`}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
      </svg>

      {open && (
        <ul className="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-neutral-300 bg-white py-1 shadow-4 dark:border-neutral-600 dark:bg-neutral-800">
          {filtered.length === 0 && (
            <li className="px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">{emptyMessage}</li>
          )}
          {filtered.map((opt, idx) => (
            <li
              key={opt.value}
              onClick={() => selectOption(opt)}
              onMouseEnter={() => setHighlighted(idx)}
              className={`cursor-pointer px-4 py-2 text-sm ${
                String(opt.value) === String(value)
                  ? 'bg-primary-50 font-semibold text-primary dark:bg-primary-900/30 dark:text-primary-300'
                  : idx === highlighted
                    ? 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-700 dark:text-neutral-200'
              }`}
            >
              {opt.label}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
