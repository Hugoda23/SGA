import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import FormInput from '../../components/FormInput'
import SearchableSelect from '../../components/SearchableSelect'
import { btn } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const PER_PAGE_ALL = { per_page: 1000 }

export default function InscripcionForm() {
  const navigate = useNavigate()
  const [modo, setModo] = useState('grado')
  const [form, setForm] = useState({ id_alumno: '', id_asignacion: '', id_grado: '', id_carrera: '', id_seccion: '', fecha_inscripcion: '' })
  const [alumnos, setAlumnos] = useState([]); const [asignaciones, setAsignaciones] = useState([]); const [grados, setGrados] = useState([]); const [carreras, setCarreras] = useState([]); const [secciones, setSecciones] = useState([])
  const [dialog, setDialog] = useState(null)

  useEffect(() => {
    api.get('/v1/alumnos', { params: PER_PAGE_ALL }).then((r) => setAlumnos(normList(r.data).map((a) => ({
      value: a.id_alumno,
      label: `${a.nombre} ${a.apellido} (${a.codigo_mineduc}) — ${a.grado?.nombre || 'sin grado'}${a.carrera ? ` / ${a.carrera.nombre_carrera}` : ''}`,
    }))))
    api.get('/v1/asignaciones', { params: PER_PAGE_ALL }).then((r) => setAsignaciones(normList(r.data).map((a) => {
      const grupo = [a.grado?.nombre, a.seccion?.nombre].filter(Boolean).join(' ')
      const docente = a.catedratico ? `${a.catedratico.nombre} ${a.catedratico.apellido}` : 'catedrático pendiente'
      return { value: a.id_asignacion, label: `${a.curso?.nombre_curso}${grupo ? ` (${grupo})` : ''} - ${docente}` }
    })))
    api.get('/v1/grados').then((r) => setGrados(r.data.map((g) => ({ value: g.id_grado, label: g.nivel ? `${g.nombre} — ${g.nivel}` : g.nombre }))))
    api.get('/v1/carreras').then((r) => setCarreras([
      { value: '', label: '— Sin carrera —' },
      ...r.data.map((c) => ({ value: c.id_carrera, label: c.nombre_carrera })),
    ]))
    api.get('/v1/secciones').then((r) => setSecciones([
      { value: '', label: '— Sin sección —' },
      ...r.data.map((s) => ({ value: s.id_seccion, label: s.nombre })),
    ]))
  }, [])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmitAsignacion = async (e) => {
    e.preventDefault()
    const payload = { id_alumno: form.id_alumno, id_asignacion: form.id_asignacion, fecha_inscripcion: form.fecha_inscripcion || null }
    try {
      await api.post('/v1/inscripciones', payload)
      navigate('/inscripciones')
    } catch (err) {
      setDialog({ type: 'error', message: err.response?.data?.errores?.join('\n') || err.response?.data?.message || 'Error al guardar' })
    }
  }

  const handleSubmitGrado = async (e) => {
    e.preventDefault()
    try {
      const res = await api.post('/v1/inscripciones/por-grado', { id_alumno: form.id_alumno, id_grado: form.id_grado, id_carrera: form.id_carrera || null, id_seccion: form.id_seccion || null })
      setDialog({ type: 'success', message: res.data.message })
    } catch (err) {
      setDialog({ type: 'error', message: err.response?.data?.errores?.join('\n') || err.response?.data?.message || 'Error al guardar' })
    }
  }

  const closeDialog = () => {
    const wasSuccess = dialog?.type === 'success'
    setDialog(null)
    if (wasSuccess) navigate('/inscripciones')
  }

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Nueva Inscripción</h1>

      <div className="mb-4 flex gap-2 rounded-xl bg-neutral-100 p-1 dark:bg-neutral-800">
        <button
          type="button"
          onClick={() => setModo('grado')}
          className={`flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition ${modo === 'grado' ? 'bg-white text-primary shadow-2 dark:bg-surface-dark' : 'text-neutral-500 dark:text-neutral-400'}`}
        >
          Por grado (pensum completo)
        </button>
        <button
          type="button"
          onClick={() => setModo('asignacion')}
          className={`flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition ${modo === 'asignacion' ? 'bg-white text-primary shadow-2 dark:bg-surface-dark' : 'text-neutral-500 dark:text-neutral-400'}`}
        >
          Asignación específica
        </button>
      </div>

      {modo === 'grado' ? (
        <form onSubmit={handleSubmitGrado} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
          <p className="text-sm text-neutral-500 dark:text-neutral-400">
            Elegí el grado (y la carrera, si aplica — Diversificado) al que va el alumno: lo inscribe en todos los cursos del
            pensum de esa combinación, para el periodo activo, y los deja guardados como su grado/carrera/sección actuales.
            Elegir la sección separa al alumno en un grupo de clase distinto al de las demás secciones del mismo grado, aunque
            compartan curso. Si un curso todavía no tiene catedrático asignado, queda inscrito de todas formas, a la espera de
            que se le asigne uno.
          </p>
          <SearchableSelect label="Alumno" name="id_alumno" value={form.id_alumno} onChange={handleChange} required options={alumnos} placeholder="Buscar alumno por nombre o código..." />
          <SearchableSelect label="Grado" name="id_grado" value={form.id_grado} onChange={handleChange} required options={grados} placeholder="Buscar grado..." />
          <SearchableSelect label="Sección" name="id_seccion" value={form.id_seccion} onChange={handleChange} options={secciones} placeholder="Buscar sección..." />
          <SearchableSelect label="Carrera (opcional, solo Diversificado)" name="id_carrera" value={form.id_carrera} onChange={handleChange} options={carreras} placeholder="Buscar carrera..." />
          <div className="flex gap-3 pt-2">
            <button type="submit" className={btn.primary} disabled={!form.id_alumno || !form.id_grado}>Inscribir a todo el pensum</button>
            <button type="button" onClick={() => navigate('/inscripciones')} className={btn.neutral}>Cancelar</button>
          </div>
        </form>
      ) : (
        <form onSubmit={handleSubmitAsignacion} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
          <SearchableSelect label="Alumno" name="id_alumno" value={form.id_alumno} onChange={handleChange} required options={alumnos} placeholder="Buscar alumno por nombre o código..." />
          <SearchableSelect label="Asignación" name="id_asignacion" value={form.id_asignacion} onChange={handleChange} required options={asignaciones} placeholder="Buscar asignación por curso o catedrático..." />
          <FormInput label="Fecha de Inscripción" name="fecha_inscripcion" type="date" value={form.fecha_inscripcion} onChange={handleChange} />
          <div className="flex gap-3 pt-2">
            <button type="submit" className={btn.primary}>Crear</button>
            <button type="button" onClick={() => navigate('/inscripciones')} className={btn.neutral}>Cancelar</button>
          </div>
        </form>
      )}

      <Modal
        open={!!dialog}
        onClose={closeDialog}
        title={dialog?.type === 'success' ? 'Inscripción completada' : 'Sistema'}
        size="sm"
        footer={
          <button className={`${btn.primary} w-full`} onClick={closeDialog}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300 whitespace-pre-line">{dialog?.message}</p>
      </Modal>
    </div>
  )
}
