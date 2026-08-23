import { useState, useRef } from 'react'
import api from '../../../api/axios'
import { btn, input, badge } from '../../../lib/twClasses'
import Modal from '../../../components/Modal'

const FORMATOS_ACEPTADOS = '.pdf,.zip,.rar,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.odt,.txt,.jpg,.jpeg,.png,.gif'

export default function MaterialesTab({ idAsignacion, unidades, materiales, reload, setAlert }) {
  const [tipo, setTipo] = useState('archivo')
  const [titulo, setTitulo] = useState('')
  const [descripcion, setDescripcion] = useState('')
  const [idUnidad, setIdUnidad] = useState('')
  const [url, setUrl] = useState('')
  const [archivo, setArchivo] = useState(null)
  const [publicando, setPublicando] = useState(false)
  const [descargando, setDescargando] = useState(null)
  const [editando, setEditando] = useState(null)
  const [materialEliminar, setMaterialEliminar] = useState(null)
  const [eliminando, setEliminando] = useState(false)
  const fileRef = useRef(null)

  const limpiar = () => {
    setTitulo(''); setDescripcion(''); setIdUnidad(''); setUrl(''); setArchivo(null); setTipo('archivo')
    if (fileRef.current) fileRef.current.value = ''
  }

  const handlePublicar = async () => {
    if (!titulo.trim()) { setAlert('El título del material es obligatorio'); return }
    if (tipo === 'archivo' && !archivo) { setAlert('Selecciona un archivo para subir'); return }
    if (tipo === 'enlace' && !url.trim()) { setAlert('Ingresa la URL del enlace'); return }
    setPublicando(true)
    try {
      const formData = new FormData()
      formData.append('id_asignacion', parseInt(idAsignacion))
      formData.append('titulo', titulo.trim())
      formData.append('tipo', tipo)
      if (idUnidad) formData.append('id_unidad', parseInt(idUnidad))
      if (descripcion.trim()) formData.append('descripcion', descripcion.trim())
      if (tipo === 'enlace') formData.append('url', url.trim())
      if (tipo === 'archivo') formData.append('archivo', archivo)
      await api.post('/v1/materiales', formData, { headers: { 'Content-Type': null } })
      setAlert('Material publicado correctamente.')
      limpiar()
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al publicar el material')
    } finally {
      setPublicando(false)
    }
  }

  const descargar = async (m) => {
    setDescargando(m.id_material)
    try {
      const res = await api.get(`/v1/archivos/${m.id_archivo}/descargar`, { responseType: 'blob' })
      const url = URL.createObjectURL(new Blob([res.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', m.archivo?.nombre || 'descarga')
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)
    } catch {
      setAlert('Error al descargar el archivo')
    } finally {
      setDescargando(null)
    }
  }

  const abrirEditar = (m) => {
    setEditando({
      id_material: m.id_material,
      titulo: m.titulo,
      descripcion: m.descripcion || '',
      id_unidad: m.id_unidad || '',
      url: m.url || '',
      tipo: m.tipo,
    })
  }

  const guardarEdicion = async () => {
    if (!editando.titulo.trim()) { setAlert('El título es obligatorio'); return }
    setPublicando(true)
    try {
      await api.put(`/v1/materiales/${editando.id_material}`, {
        titulo: editando.titulo.trim(),
        descripcion: editando.descripcion.trim() || null,
        id_unidad: editando.id_unidad ? parseInt(editando.id_unidad) : null,
        url: editando.url.trim() || null,
      })
      setEditando(null)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al guardar el material')
    } finally {
      setPublicando(false)
    }
  }

  const eliminar = async () => {
    setEliminando(true)
    try {
      await api.delete(`/v1/materiales/${materialEliminar}`)
      setMaterialEliminar(null)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al eliminar el material')
    } finally {
      setEliminando(false)
    }
  }

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
      {/* Formulario */}
      <div className="flex flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="border-b border-primary-100/50 bg-primary-50 p-6 dark:border-primary-900/50 dark:bg-primary-900/20">
          <h2 className="flex items-center gap-2 text-xl font-bold text-primary">
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Subir Material
          </h2>
        </div>
        <div className="flex flex-1 flex-col gap-5 p-6">
          <div>
            <label className={input.label}>Título</label>
            <input type="text" value={titulo} onChange={(e) => setTitulo(e.target.value)} placeholder="Ej. Presentación Unidad 1" className={input.base} />
          </div>
          <div>
            <label className={input.label}>Semana / Unidad vinculada (opcional)</label>
            <select value={idUnidad} onChange={(e) => setIdUnidad(e.target.value)} className={input.base}>
              <option value="">Sin vincular</option>
              {unidades.map((u) => (
                <option key={u.id_unidad} value={u.id_unidad}>
                  {u.numero_semana ? `Semana ${u.numero_semana} — ` : ''}{u.titulo}
                </option>
              ))}
            </select>
          </div>
          <div className="flex gap-4">
            <label className="flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
              <input type="radio" name="tipoMaterial" checked={tipo === 'archivo'} onChange={() => setTipo('archivo')} className="h-4 w-4 accent-primary" />
              Archivo
            </label>
            <label className="flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
              <input type="radio" name="tipoMaterial" checked={tipo === 'enlace'} onChange={() => setTipo('enlace')} className="h-4 w-4 accent-primary" />
              Enlace externo
            </label>
          </div>
          {tipo === 'archivo' ? (
            <div>
              <label className={input.label}>Archivo (máx. 20MB)</label>
              <input
                ref={fileRef}
                type="file"
                accept={FORMATOS_ACEPTADOS}
                onChange={(e) => setArchivo(e.target.files[0] || null)}
                className="block w-full text-sm text-neutral-600 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-xs file:font-bold file:uppercase file:text-white hover:file:bg-primary-accent-300 dark:text-neutral-300"
              />
            </div>
          ) : (
            <div>
              <label className={input.label}>URL del enlace</label>
              <input type="url" value={url} onChange={(e) => setUrl(e.target.value)} placeholder="https://..." className={input.base} />
            </div>
          )}
          <div>
            <label className={input.label}>Descripción (opcional)</label>
            <textarea rows="2" value={descripcion} onChange={(e) => setDescripcion(e.target.value)} placeholder="Breve descripción del material" className={`${input.base} resize-none`}></textarea>
          </div>
          <div className="mt-2 flex justify-end">
            <button onClick={handlePublicar} disabled={publicando} className={`${btn.primary} disabled:opacity-50`}>
              {publicando ? 'Publicando...' : 'Publicar material'}
            </button>
          </div>
        </div>
      </div>

      {/* Lista */}
      <div className="flex flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
          <h3 className="text-sm font-bold text-neutral-700 dark:text-neutral-200">Materiales publicados ({materiales.length})</h3>
        </div>
        <div className="flex-1 space-y-3 p-6">
          {materiales.length === 0 ? (
            <div className="py-10 text-center text-sm text-neutral-400 dark:text-neutral-500">Aún no has publicado materiales.</div>
          ) : (
            materiales.map((m) => (
              <div key={m.id_material} className="rounded-xl border border-neutral-100 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-700/50">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="truncate text-sm font-bold text-neutral-700 dark:text-neutral-200">{m.titulo}</p>
                      <span className={m.tipo === 'archivo' ? badge.info : badge.warning}>
                        {m.tipo === 'archivo' ? 'Archivo' : 'Enlace'}
                      </span>
                    </div>
                    {m.id_unidad && (
                      <p className="mt-1 text-xs font-semibold text-primary">
                        Semana {m.unidad ? `${m.unidad.numero_semana} · ` : ''}{m.unidad?.titulo || ''}
                      </p>
                    )}
                    {m.descripcion && <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{m.descripcion}</p>}
                    <p className="mt-1 text-[11px] text-neutral-400">
                      {m.fecha_publicacion ? new Date(m.fecha_publicacion).toLocaleString('es-GT') : ''}
                    </p>
                  </div>
                  <div className="flex shrink-0 gap-2">
                    {m.tipo === 'archivo' ? (
                      <button onClick={() => descargar(m)} disabled={descargando === m.id_material} className={`${btn.outline} !px-3 !py-1.5 disabled:opacity-50`}>
                        {descargando === m.id_material ? '...' : 'Descargar'}
                      </button>
                    ) : (
                      <a href={m.url} target="_blank" rel="noreferrer" className={`${btn.outline} !px-3 !py-1.5`}>Abrir</a>
                    )}
                    <button onClick={() => abrirEditar(m)} className={`${btn.outline} !px-3 !py-1.5`}>Editar</button>
                    <button onClick={() => setMaterialEliminar(m.id_material)} className={`${btn.outlineDanger} !px-3 !py-1.5`}>Eliminar</button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {/* Modal editar */}
      <Modal
        open={!!editando}
        onClose={() => setEditando(null)}
        title="Editar material"
        size="md"
        footer={
          <>
            <button onClick={() => setEditando(null)} className={btn.neutral}>Cancelar</button>
            <button onClick={guardarEdicion} disabled={publicando} className={`${btn.primary} disabled:opacity-50`}>
              {publicando ? 'Guardando...' : 'Guardar cambios'}
            </button>
          </>
        }
      >
        {editando && (
          <div className="space-y-4">
            <div>
              <label className={input.label}>Título</label>
              <input type="text" value={editando.titulo} onChange={(e) => setEditando({ ...editando, titulo: e.target.value })} className={input.base} />
            </div>
            <div>
              <label className={input.label}>Semana / Unidad vinculada (opcional)</label>
              <select value={editando.id_unidad} onChange={(e) => setEditando({ ...editando, id_unidad: e.target.value })} className={input.base}>
                <option value="">Sin vincular</option>
                {unidades.map((u) => (
                  <option key={u.id_unidad} value={u.id_unidad}>
                    {u.numero_semana ? `Semana ${u.numero_semana} — ` : ''}{u.titulo}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className={input.label}>Descripción</label>
              <textarea rows="2" value={editando.descripcion} onChange={(e) => setEditando({ ...editando, descripcion: e.target.value })} className={`${input.base} resize-none`}></textarea>
            </div>
            {editando.tipo === 'enlace' && (
              <div>
                <label className={input.label}>URL</label>
                <input type="url" value={editando.url} onChange={(e) => setEditando({ ...editando, url: e.target.value })} className={input.base} />
              </div>
            )}
          </div>
        )}
      </Modal>

      {/* Modal eliminar */}
      <Modal
        open={!!materialEliminar}
        onClose={() => setMaterialEliminar(null)}
        title="Eliminar material"
        size="sm"
        footer={
          <>
            <button onClick={() => setMaterialEliminar(null)} className={btn.neutral}>Cancelar</button>
            <button onClick={eliminar} disabled={eliminando} className={`${btn.danger} disabled:opacity-50`}>
              {eliminando ? 'Eliminando...' : 'Eliminar'}
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">¿Seguro que deseas eliminar este material?</p>
      </Modal>
    </div>
  )
}
