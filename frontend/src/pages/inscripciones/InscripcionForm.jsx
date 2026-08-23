import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import FormInput from '../../components/FormInput'
import SearchableSelect from '../../components/SearchableSelect'
import { btn } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function InscripcionForm() {
  const navigate = useNavigate()
  const [form, setForm] = useState({ id_alumno: '', id_asignacion: '', fecha_inscripcion: '' })
  const [alumnos, setAlumnos] = useState([]); const [asignaciones, setAsignaciones] = useState([])
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    api.get('/v1/alumnos').then((r) => setAlumnos(normList(r.data).map((a) => ({ value: a.id_alumno, label: `${a.nombre} ${a.apellido} (${a.codigo_mineduc})` }))))
    api.get('/v1/asignaciones').then((r) => setAsignaciones(normList(r.data).map((a) => ({ value: a.id_asignacion, label: `${a.curso?.nombre_curso} - ${a.catedratico?.nombre} ${a.catedratico?.apellido}` }))))
  }, [])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })
  const handleSubmit = async (e) => {
    e.preventDefault()
    const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
    try {
      await api.post('/v1/inscripciones', payload)
      navigate('/inscripciones')
    } catch (err) {
      if (err.response?.data?.errors) {
        const msgs = Object.values(err.response.data.errors).flat().join('\n')
        setAlertMessage(msgs)
      } else {
        setAlertMessage(err.response?.data?.message || 'Error al guardar')
      }
    }
  }

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Nueva Inscripción</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <SearchableSelect label="Alumno" name="id_alumno" value={form.id_alumno} onChange={handleChange} required options={alumnos} placeholder="Buscar alumno por nombre o código..." />
        <SearchableSelect label="Asignación" name="id_asignacion" value={form.id_asignacion} onChange={handleChange} required options={asignaciones} placeholder="Buscar asignación por curso o catedrático..." />
        <FormInput label="Fecha de Inscripción" name="fecha_inscripcion" type="date" value={form.fecha_inscripcion} onChange={handleChange} />
        <div className="flex gap-3 pt-2">
          <button type="submit" className={btn.primary}>Crear</button>
          <button type="button" onClick={() => navigate('/inscripciones')} className={btn.neutral}>Cancelar</button>
        </div>
      </form>

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button className={`${btn.primary} w-full`} onClick={() => setAlertMessage(null)}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300 whitespace-pre-line">{alertMessage}</p>
      </Modal>
    </div>
  )
}
