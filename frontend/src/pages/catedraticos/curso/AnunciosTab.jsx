import { useState } from 'react'
import api from '../../../api/axios'
import { btn, input } from '../../../lib/twClasses'
import Modal from '../../../components/Modal'

export default function AnunciosTab({ idAsignacion, anuncios, reload, setAlert }) {
  const [titulo, setTitulo] = useState('')
  const [contenido, setContenido] = useState('')
  const [publicando, setPublicando] = useState(false)
  const [editando, setEditando] = useState(null)
  const [anuncioEliminar, setAnuncioEliminar] = useState(null)
  const [eliminando, setEliminando] = useState(false)

  const limpiar = () => { setTitulo(''); setContenido('') }

  const handlePublicar = async () => {
    if (!titulo.trim()) { setAlert('El título del anuncio es obligatorio'); return }
    setPublicando(true)
    try {
      await api.post('/v1/anuncios', {
        id_asignacion: parseInt(idAsignacion),
        titulo: titulo.trim(),
        contenido: contenido.trim() || null,
      })
      setAlert('Anuncio publicado. Los alumnos recibirán una notificación.')
      limpiar()
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al publicar el anuncio')
    } finally {
      setPublicando(false)
    }
  }

  const abrirEditar = (a) => {
    setEditando({ id_anuncio: a.id_anuncio, titulo: a.titulo, contenido: a.contenido || '' })
  }

  const guardarEdicion = async () => {
    if (!editando.titulo.trim()) { setAlert('El título es obligatorio'); return }
    setPublicando(true)
    try {
      await api.put(`/v1/anuncios/${editando.id_anuncio}`, {
        titulo: editando.titulo.trim(),
        contenido: editando.contenido.trim() || null,
      })
      setEditando(null)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al guardar el anuncio')
    } finally {
      setPublicando(false)
    }
  }

  const eliminar = async () => {
    setEliminando(true)
    try {
      await api.delete(`/v1/anuncios/${anuncioEliminar}`)
      setAnuncioEliminar(null)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al eliminar el anuncio')
    } finally {
      setEliminando(false)
    }
  }

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div className="flex flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="border-b border-primary-100/50 bg-primary-50 p-6 dark:border-primary-900/50 dark:bg-primary-900/20">
          <h2 className="flex items-center gap-2 text-xl font-bold text-primary">
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
            Nuevo Anuncio
          </h2>
        </div>
        <div className="flex flex-1 flex-col gap-5 p-6">
          <div>
            <label className={input.label}>Título</label>
            <input type="text" value={titulo} onChange={(e) => setTitulo(e.target.value)} placeholder="Ej. Examen parcial la próxima semana" className={input.base} />
          </div>
          <div>
            <label className={input.label}>Contenido / Detalle</label>
            <textarea rows="5" value={contenido} onChange={(e) => setContenido(e.target.value)} placeholder="Escribe el contenido del anuncio..." className={`${input.base} resize-none`}></textarea>
          </div>
          <div className="mt-2 flex justify-end">
            <button onClick={handlePublicar} disabled={publicando} className={`${btn.primary} disabled:opacity-50`}>
              {publicando ? 'Publicando...' : 'Publicar anuncio'}
            </button>
          </div>
        </div>
      </div>

      <div className="flex flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
          <h3 className="text-sm font-bold text-neutral-700 dark:text-neutral-200">Anuncios publicados ({anuncios.length})</h3>
        </div>
        <div className="flex-1 space-y-3 p-6">
          {anuncios.length === 0 ? (
            <div className="py-10 text-center text-sm text-neutral-400 dark:text-neutral-500">Aún no has publicado anuncios.</div>
          ) : (
            anuncios.map((a) => (
              <div key={a.id_anuncio} className="rounded-xl border border-neutral-100 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-700/50">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-bold text-neutral-700 dark:text-neutral-200">{a.titulo}</p>
                    {a.contenido && <p className="mt-1 whitespace-pre-line text-xs text-neutral-500 dark:text-neutral-400">{a.contenido}</p>}
                    <p className="mt-1 text-[11px] font-semibold text-primary">
                      {a.fecha_publicacion ? new Date(a.fecha_publicacion).toLocaleString('es-GT') : ''}
                    </p>
                  </div>
                  <div className="flex shrink-0 gap-2">
                    <button onClick={() => abrirEditar(a)} className={`${btn.outline} !px-3 !py-1.5`}>Editar</button>
                    <button onClick={() => setAnuncioEliminar(a.id_anuncio)} className={`${btn.outlineDanger} !px-3 !py-1.5`}>Eliminar</button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      <Modal
        open={!!editando}
        onClose={() => setEditando(null)}
        title="Editar anuncio"
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
              <label className={input.label}>Contenido</label>
              <textarea rows="4" value={editando.contenido} onChange={(e) => setEditando({ ...editando, contenido: e.target.value })} className={`${input.base} resize-none`}></textarea>
            </div>
          </div>
        )}
      </Modal>

      <Modal
        open={!!anuncioEliminar}
        onClose={() => setAnuncioEliminar(null)}
        title="Eliminar anuncio"
        size="sm"
        footer={
          <>
            <button onClick={() => setAnuncioEliminar(null)} className={btn.neutral}>Cancelar</button>
            <button onClick={eliminar} disabled={eliminando} className={`${btn.danger} disabled:opacity-50`}>
              {eliminando ? 'Eliminando...' : 'Eliminar'}
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">¿Seguro que deseas eliminar este anuncio?</p>
      </Modal>
    </div>
  )
}
