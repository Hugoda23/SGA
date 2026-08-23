import { useState, useEffect } from 'react'
import api, { normList } from '../../api/axios'
import { btn, input, table as tbl } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function ReporteNotas() {
  const [search, setSearch] = useState('')
  const [alumnos, setAlumnos] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    const fetchAlumnos = async () => {
      try {
        const response = await api.get('/v1/alumnos');
        setAlumnos(normList(response.data));
      } catch (error) {
        console.error('Error fetching alumnos', error);
      } finally {
        setLoading(false);
      }
    }
    fetchAlumnos();
  }, []);

  const handleDownloadBoletin = async (id_alumno) => {
    try {
      const response = await api.get(`/v1/reportes/pdf/boletin/${id_alumno}`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `boletin_alumno_${id_alumno}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Error al descargar el PDF', error);
      setAlertMessage('Error al descargar el Boletín. Verifica que el backend esté ejecutándose.');
    }
  }

  const handleDownloadKardex = async (id_alumno) => {
    try {
      const response = await api.get(`/v1/reportes/pdf/kardex/${id_alumno}`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `kardex_alumno_${id_alumno}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Error al descargar el PDF', error);
      setAlertMessage('Error al descargar el Kárdex.');
    }
  }

  const filteredAlumnos = alumnos.filter(a => 
    (a.nombre && a.nombre.toLowerCase().includes(search.toLowerCase())) || 
    (a.apellido && a.apellido.toLowerCase().includes(search.toLowerCase())) ||
    (a.codigo_mineduc && a.codigo_mineduc.toLowerCase().includes(search.toLowerCase())) ||
    (a.id_alumno && a.id_alumno.toString().includes(search))
  );

  return (
    <div className="max-w-7xl mx-auto pb-12">

      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100 mb-2">Reporte de Notas (Alumnos)</h1>
        <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Genera boletines de calificaciones y Kárdex (historial académico).</p>
      </div>

      {/* Table Container */}
      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        
        {/* Filter Bar */}
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar por nombre o carnet..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
        </div>

        {/* Table */}
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>CARNET</th>
                <th className={tbl.th}>ALUMNO</th>
                <th className={tbl.th}>CORREO</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="4" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3 text-neutral-500">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold">Cargando alumnos...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredAlumnos.length === 0 ? (
                <tr>
                  <td colSpan="4" className="px-4 py-12 text-center text-neutral-500">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="w-12 h-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                      <span className="font-semibold text-neutral-600 dark:text-neutral-300">No hay datos disponibles</span>
                    </div>
                    <p className="text-sm text-neutral-400">Realiza una búsqueda diferente.</p>
                  </td>
                </tr>
              ) : (
                filteredAlumnos.map((row) => (
                  <tr key={row.id_alumno} className={tbl.row}>
                    <td className={`${tbl.td} font-medium`}>{row.codigo_mineduc || `MAT-${row.id_alumno}`}</td>
                    <td className={`${tbl.td} font-medium`}>{row.nombre} {row.apellido}</td>
                    <td className={tbl.td}>{row.correo}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-right">
                      <div className="flex justify-end gap-2">
                        <button 
                          onClick={() => handleDownloadBoletin(row.id_alumno)}
                          className="rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-100/10"
                          title="Descargar Boletín"
                        >
                          PDF Boletín
                        </button>
                        <button 
                          onClick={() => handleDownloadKardex(row.id_alumno)}
                          className="rounded-lg bg-info-50 px-3 py-1.5 text-xs font-bold text-info transition-colors hover:bg-info hover:text-white dark:bg-info-100/10"
                          title="Descargar Kárdex"
                        >
                          PDF Kárdex
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300 mb-2">{alertMessage}</p>
      </Modal>
    </div>
  )
}
