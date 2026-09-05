import { useState, useEffect, useRef } from 'react'
import { btn, input, table as tbl } from '../lib/twClasses'
import Modal from './Modal'

export default function DataTable({ columns, data, onEdit, onDelete, title, onAdd, loading, subtitle, headerExtra, rowActions, pagination, onPageChange, onSearch, onExport }) {
  const [search, setSearch] = useState('')
  const [confirmRow, setConfirmRow] = useState(null)
  const [deleteError, setDeleteError] = useState(null)
  const [exporting, setExporting] = useState(false)

  // onSearch llega como función anónima desde las páginas, así que cambia de
  // identidad en cada render. Si estuviera en las dependencias del efecto de
  // abajo, ese efecto se re-ejecutaría en cada render y su temporizador
  // llamaría a onSearch(search), que en todas las páginas hace setPage(1):
  // al pasar a la página 2 se cargaba y volvía sola a la 1. Guardándola en un
  // ref, el efecto depende solo de "search" y corre únicamente al teclear.
  const onSearchRef = useRef(onSearch)
  useEffect(() => {
    onSearchRef.current = onSearch
  })

  const yaMontado = useRef(false)
  useEffect(() => {
    if (!onSearchRef.current) return
    // En el montaje la página ya hace su propia carga inicial; disparar la
    // búsqueda aquí solo provocaría una segunda petición idéntica.
    if (!yaMontado.current) {
      yaMontado.current = true
      return
    }
    const t = setTimeout(() => onSearchRef.current(search), 350)
    return () => clearTimeout(t)
  }, [search])

  const escaparCSV = (valor) => {
    const texto = String(valor ?? '').replace(/\s+/g, ' ').trim()
    return /[",;\n]/.test(texto) ? `"${texto.replace(/"/g, '""')}"` : texto
  }

  const handleExport = async () => {
    if (!onExport || exporting) return
    setExporting(true)
    try {
      const filas = await onExport()
      if (!Array.isArray(filas) || filas.length === 0) {
        setDeleteError('No hay datos para exportar.')
        return
      }
      const encabezado = columns.map((c) => escaparCSV(c.label)).join(';')
      const lineas = filas.map((row) =>
        columns
          .map((c) => escaparCSV(c.exportValue ? c.exportValue(row) : row[c.key]))
          .join(';')
      )
      const csv = [encabezado, ...lineas].join('\n')
      const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${title.replace(/\s+/g, '_').toLowerCase()}_${new Date().toISOString().slice(0, 10)}.csv`
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (e) {
      console.error(e)
      setDeleteError('No se pudo exportar el archivo.')
    } finally {
      setExporting(false)
    }
  }

  const isPaginated = data && !Array.isArray(data) && Array.isArray(data.data)
  const rows = isPaginated ? data.data : (Array.isArray(data) ? data : [])
  const page = pagination?.current_page ?? (isPaginated ? data.current_page : 1)
  const lastPage = pagination?.last_page ?? (isPaginated ? data.last_page : 1)
  const total = pagination?.total ?? (isPaginated ? data.total : rows.length)

  const filtered = onSearch ? rows : rows.filter((row) =>
    columns.some((col) =>
      String(row[col.key] ?? '').toLowerCase().includes(search.toLowerCase())
    )
  )

  const paginas = (() => {
    const paginas = new Set([1, lastPage, page - 1, page, page + 1])
    const orden = [...paginas].filter((p) => p >= 1 && p <= lastPage).sort((a, b) => a - b)
    const resultado = []
    let anterior = 0
    for (const p of orden) {
      if (p - anterior > 1) resultado.push('...')
      resultado.push(p)
      anterior = p
    }
    return resultado
  })()

  const hasActions = !!(onEdit || onDelete || rowActions?.length)
  const colSpan = columns.length + (hasActions ? 1 : 0)

  const handleConfirmDelete = async () => {
    const row = confirmRow
    setConfirmRow(null)
    if (onDelete && row) {
      try {
        await onDelete(row)
      } catch (e) {
        setDeleteError(e.response?.data?.message || 'No se pudo eliminar el registro porque tiene registros asociados.')
      }
    }
  }

  const celda = (col, row) => (col.render ? col.render(row) : row[col.key] ?? '-')

  const acciones = (row) => (
    <div className="flex flex-wrap justify-end gap-2">
      {onEdit && (
        <button
          type="button"
          onClick={() => onEdit(row)}
          className="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-warning transition-colors hover:bg-warning hover:text-white dark:bg-amber-100/10"
        >
          Editar
        </button>
      )}
      {rowActions?.map((action) => {
        if (action.show && !action.show(row)) return null
        return (
          <button
            key={action.label}
            type="button"
            onClick={() => action.onClick(row)}
            className={action.className}
          >
            {action.label}
          </button>
        )
      })}
      {onDelete && (
        <button
          type="button"
          onClick={() => setConfirmRow(row)}
          className="rounded-lg bg-danger-50 px-3 py-1.5 text-xs font-bold text-danger transition-colors hover:bg-danger hover:text-white dark:bg-danger-100/10"
        >
          Eliminar
        </button>
      )}
    </div>
  )

  const cargando = (
    <div className="flex flex-col items-center gap-3 py-12">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
      <span className="font-semibold text-neutral-500">Cargando datos...</span>
    </div>
  )

  const vacio = (
    <div className="flex flex-col items-center justify-center px-4 py-12 text-center">
      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
      </svg>
      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay registros</span>
      <p className="text-sm text-neutral-400">Realiza una búsqueda diferente o crea un nuevo registro.</p>
    </div>
  )

  return (
    <div className="mx-auto max-w-7xl px-4 pb-12 sm:px-6">
      <div className="mb-6 flex flex-col gap-4 sm:mb-8 md:flex-row md:items-end md:justify-between md:gap-6">
        <div className="min-w-0">
          <h1 className="mb-2 text-2xl font-bold text-neutral-800 dark:text-neutral-100 sm:text-3xl">{title}</h1>
          <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400 sm:text-base">
            {subtitle || 'Gestiona y administra los registros de este módulo.'}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {headerExtra}
          {onExport && (
            <button type="button" onClick={handleExport} disabled={exporting} className={`${btn.outline} flex-1 sm:flex-none`}>
              {exporting ? (
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-neutral-300 border-t-primary"></span>
              ) : (
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
              )}
              Exportar CSV
            </button>
          )}
          {onAdd && (
            <button type="button" onClick={onAdd} className={`${btn.primary} flex-1 sm:flex-none`}>
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Nuevo Registro
            </button>
          )}
        </div>
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              type="text"
              placeholder="Buscar registros..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
        </div>

        {/* Tabla: solo desde lg. Con muchas columnas, por debajo de ese ancho
            obliga a un scroll horizontal incómodo, así que ahí se usan
            tarjetas apiladas. */}
        <div className="hidden overflow-x-auto lg:block">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                {columns.map((col) => (
                  <th key={col.key} className={tbl.th}>
                    {col.label}
                  </th>
                ))}
                {hasActions && <th className={`${tbl.th} text-right`}>Acciones</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan={colSpan}>{cargando}</td>
                </tr>
              ) : filtered.length === 0 ? (
                <tr>
                  <td colSpan={colSpan}>{vacio}</td>
                </tr>
              ) : (
                filtered.map((row, idx) => (
                  <tr key={row.id || idx} className={tbl.row}>
                    {columns.map((col) => (
                      <td key={col.key} className={tbl.td}>
                        {celda(col, row)}
                      </td>
                    ))}
                    {hasActions && (
                      <td className="whitespace-nowrap px-4 py-3 text-right">{acciones(row)}</td>
                    )}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Tarjetas: por debajo de lg. Cada registro se apila con su etiqueta
            al lado, sin scroll horizontal. */}
        <div className="divide-y divide-neutral-100 dark:divide-neutral-700 lg:hidden">
          {loading ? (
            cargando
          ) : filtered.length === 0 ? (
            vacio
          ) : (
            filtered.map((row, idx) => (
              <div key={row.id || idx} className="space-y-2 p-4">
                {columns.map((col) => (
                  <div key={col.key} className="flex items-start justify-between gap-3">
                    <span className="shrink-0 text-xs font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                      {col.label}
                    </span>
                    <span className="min-w-0 break-words text-right text-sm text-neutral-700 dark:text-neutral-200">
                      {celda(col, row)}
                    </span>
                  </div>
                ))}
                {hasActions && <div className="pt-2">{acciones(row)}</div>}
              </div>
            ))
          )}
        </div>

        {lastPage > 1 && onPageChange && (
          <div className="flex flex-col items-center justify-between gap-3 border-t border-neutral-100 p-4 dark:border-neutral-700 sm:flex-row">
            <p className="text-center text-sm font-medium text-neutral-500 dark:text-neutral-400 sm:text-left">
              Mostrando página {page} de {lastPage} · {total} registro{total !== 1 ? 's' : ''}
            </p>
            <div className="flex flex-wrap items-center justify-center gap-1">
              <button
                type="button"
                onClick={() => onPageChange(page - 1)}
                disabled={page <= 1}
                className="rounded-lg border border-neutral-200 px-3 py-1.5 text-sm font-bold text-neutral-600 transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700"
              >
                Anterior
              </button>
              {paginas.map((p, i) =>
                p === '...' ? (
                  <span key={`e-${i}`} className="px-2 text-neutral-400">…</span>
                ) : (
                  <button
                    key={p}
                    type="button"
                    onClick={() => onPageChange(p)}
                    className={`rounded-lg px-3 py-1.5 text-sm font-bold transition-colors ${
                      p === page
                        ? 'bg-primary text-white'
                        : 'border border-neutral-200 text-neutral-600 hover:bg-neutral-100 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700'
                    }`}
                  >
                    {p}
                  </button>
                )
              )}
              <button
                type="button"
                onClick={() => onPageChange(page + 1)}
                disabled={page >= lastPage}
                className="rounded-lg border border-neutral-200 px-3 py-1.5 text-sm font-bold text-neutral-600 transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700"
              >
                Siguiente
              </button>
            </div>
          </div>
        )}
      </div>

      <Modal
        open={!!confirmRow}
        onClose={() => setConfirmRow(null)}
        title="Confirmación"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setConfirmRow(null)} className={btn.ghost}>
              Cancelar
            </button>
            <button type="button" onClick={handleConfirmDelete} className={btn.danger}>
              Sí, Eliminar
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          ¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.
        </p>
      </Modal>

      <Modal
        open={!!deleteError}
        onClose={() => setDeleteError(null)}
        title="Mensaje del Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setDeleteError(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{deleteError}</p>
      </Modal>
    </div>
  )
}
