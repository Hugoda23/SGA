import { useState, useEffect, useCallback } from 'react'
import { useNavigate, useParams, useSearchParams } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import FormInput from '../../components/FormInput'
import { btn, input } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const DIAS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']

export default function AsignacionForm() {
  const { id } = useParams(); const isEdit = !!id; const navigate = useNavigate()
  const [searchParams] = useSearchParams();
  const initialCursoId = searchParams.get('curso_id') || '';
  const [form, setForm] = useState({
    id_catedratico: '',
    id_curso: initialCursoId,
    id_aula: '',
    id_periodo: '',
    id_grado: '',
    id_seccion: ''
  })
  const [catedraticos, setCatedraticos] = useState([])
  const [cursos, setCursos] = useState([])
  const [aulas, setAulas] = useState([])
  const [periodos, setPeriodos] = useState([])
  const [grados, setGrados] = useState([])
  const [secciones, setSecciones] = useState([])
  const [horarios, setHorarios] = useState([])
  const [nuevoHorario, setNuevoHorario] = useState({ dia_semana: 'Lunes', hora_inicio: '07:00:00', hora_fin: '08:00:00' })
  const [alertMessage, setAlertMessage] = useState(null)

  const fetchHorarios = useCallback(() => {
    if (!isEdit) return
    api.get(`/v1/horarios`).then((r) => {
      setHorarios(r.data.filter((h) => h.id_asignacion === parseInt(id)))
    }).catch(console.error)
  }, [id, isEdit])

  useEffect(() => {
    api.get('/v1/catedraticos').then((r) => setCatedraticos(normList(r.data).map((c) => ({ value: c.id_catedratico, label: `${c.nombre} ${c.apellido}` }))))
    api.get('/v1/cursos').then((r) => setCursos(r.data.map((c) => ({ value: c.id_curso, label: c.nombre_curso }))))
    api.get('/v1/aulas').then((r) => setAulas(r.data.map((a) => ({ value: a.id_aula, label: a.nombre_aula }))))
    api.get('/v1/periodos-academicos').then((r) => setPeriodos(r.data.map((p) => ({ value: p.id_periodo, label: p.nombre }))))
    api.get('/v1/grados').then((r) => setGrados(r.data.map((g) => ({ value: g.id_grado, label: g.nivel ? `${g.nombre} — ${g.nivel}` : g.nombre }))))
    api.get('/v1/secciones').then((r) => setSecciones(r.data.map((s) => ({ value: s.id_seccion, label: s.nombre }))))
    if (isEdit) api.get(`/v1/asignaciones/${id}`).then((r) => {
      const a = r.data
      setForm({
        id_catedratico: a.id_catedratico || '',
        id_curso: a.id_curso || '',
        id_aula: a.id_aula || '',
        id_periodo: a.id_periodo || '',
        id_grado: a.id_grado || '',
        id_seccion: a.id_seccion || ''
      })
    })
    fetchHorarios()
  }, [id, isEdit, fetchHorarios])

  const agregarHorario = async () => {
    try {
      await api.post('/v1/horarios', { ...nuevoHorario, id_asignacion: parseInt(id) })
      setNuevoHorario({ dia_semana: 'Lunes', hora_inicio: '07:00:00', hora_fin: '08:00:00' })
      fetchHorarios()
    } catch (err) {
      setAlertMessage(err.response?.data?.errores?.join('\n') || err.response?.data?.message || 'Error al agregar horario')
    }
  }

  const eliminarHorario = async (id_horario) => {
    try {
      await api.delete(`/v1/horarios/${id_horario}`)
      fetchHorarios()
    } catch {
      setAlertMessage('Error al eliminar horario')
    }
  }

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })
  const handleSubmit = async (e) => {
    e.preventDefault()
    const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
    try {
      if (isEdit) {
        await api.put(`/v1/asignaciones/${id}`, payload)
        navigate('/asignaciones')
      } else {
        const res = await api.post('/v1/asignaciones', payload)
        // En vez de volver al listado, pasamos al modo edición de la
        // asignación recién creada — ahí es donde vive la sección de
        // horarios, para no obligar a guardar y volver a entrar.
        setAlertMessage('Asignación creada. Ahora agrega sus horarios de clase abajo.')
        navigate(`/asignaciones/${res.data.id_asignacion}`, { replace: true })
      }
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
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nueva'} Asignación</h1>
      <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
        <FormInput label="Catedrático" name="id_catedratico" type="select" value={form.id_catedratico} onChange={handleChange} required options={catedraticos} />
        <FormInput label="Curso" name="id_curso" type="select" value={form.id_curso} onChange={handleChange} required options={cursos} />
        <FormInput label="Aula" name="id_aula" type="select" value={form.id_aula} onChange={handleChange} required options={aulas} />
        <FormInput label="Periodo Académico" name="id_periodo" type="select" value={form.id_periodo} onChange={handleChange} required options={periodos} />
        <div className="grid grid-cols-2 gap-4">
          <FormInput
            label="Grado"
            name="id_grado"
            type="select"
            value={form.id_grado}
            onChange={handleChange}
            options={grados}
            placeholder="— Seleccionar grado —"
          />
          <FormInput
            label="Sección"
            name="id_seccion"
            type="select"
            value={form.id_seccion}
            onChange={handleChange}
            options={secciones}
            placeholder="— Seleccionar sección —"
          />
        </div>

        {/* Info si no hay grados o secciones cargados */}
        {(grados.length === 0 || secciones.length === 0) && (
          <p className="text-xs text-warning bg-warning-50 rounded-lg px-3 py-2 border border-warning-200">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg> {grados.length === 0 && 'No hay grados registrados. '}
            {secciones.length === 0 && 'No hay secciones registradas. '}
            Ve a <strong>Infraestructura</strong> para crearlos primero.
          </p>
        )}

        <div className="flex gap-3 pt-2">
          <button type="submit" className={btn.primary}>{isEdit ? 'Actualizar' : 'Crear'}</button>
          <button type="button" onClick={() => navigate('/asignaciones')} className={btn.neutral}>{isEdit ? 'Volver a la lista' : 'Cancelar'}</button>
        </div>
      </form>

        {isEdit && (
          <div className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
            <h3 className="text-lg font-bold text-neutral-700 dark:text-neutral-200">Horarios de Clase</h3>

            {horarios.length > 0 && (
              <div className="space-y-2">
                {horarios.map((h) => (
                  <div key={h.id_horario} className="flex items-center gap-3 rounded-xl bg-neutral-50 px-4 py-3 border border-neutral-100 dark:border-neutral-700">
                    <span className="font-bold text-primary w-24">{h.dia_semana}</span>
                    <span className="text-sm text-neutral-600 dark:text-neutral-300">{h.hora_inicio?.substring(0, 5)} — {h.hora_fin?.substring(0, 5)}</span>
                    <button onClick={() => eliminarHorario(h.id_horario)} className="ml-auto text-sm font-bold text-danger hover:text-red-600">Eliminar</button>
                  </div>
                ))}
              </div>
            )}

            <div className="flex items-end gap-3">
              <div className="flex-1">
                <label className={input.label}>Día</label>
                <select value={nuevoHorario.dia_semana} onChange={(e) => setNuevoHorario({ ...nuevoHorario, dia_semana: e.target.value })} className={input.base}>
                  {DIAS.map((d) => <option key={d} value={d}>{d}</option>)}
                </select>
              </div>
              <div className="flex-1">
                <label className={input.label}>Inicio</label>
                <input type="time" value={nuevoHorario.hora_inicio.substring(0, 5)} onChange={(e) => setNuevoHorario({ ...nuevoHorario, hora_inicio: `${e.target.value}:00` })} className={input.base} />
              </div>
              <div className="flex-1">
                <label className={input.label}>Fin</label>
                <input type="time" value={nuevoHorario.hora_fin.substring(0, 5)} onChange={(e) => setNuevoHorario({ ...nuevoHorario, hora_fin: `${e.target.value}:00` })} className={input.base} />
              </div>
              <button onClick={agregarHorario} className={`${btn.primary} shrink-0`}>+ Agregar</button>
            </div>
          </div>
        )}

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
