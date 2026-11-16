<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';

const view = ref('dashboard');
const loading = ref(true);
const busy = ref(false);
const toast = ref({ show: false, message: '', type: 'success' });
const modal = ref('');
const editingId = ref(null);
const search = ref('');
const appointmentFilter = ref('');
const dateFilter = ref('');
const patients = ref([]);
const appointments = ref([]);
const doctors = ref([]);
const records = ref([]);
const dashboard = ref({ stats: {}, week: [], todayAppointments: [], recentRecords: [] });
const report = ref({ total: 0, completed: 0, completionRate: 0, daily: [], byService: [] });
const errors = ref({});
let toastTimer;

const blankPatient = () => ({ Nama_Pasien: '', No_Hp: '', Alamat_Pasien: '', jenis_kelamin: '', tanggal_lahir: '', golongan_darah: '', alergi: '' });
const blankAppointment = () => ({ pasien_id: '', doctor_id: '', service_name: 'Konsultasi umum', scheduled_at: '', reason: '' });
const blankRecord = () => ({ pasien_id: '', doctor_id: '', appointment_id: '', visited_at: localDateTime(), complaint: '', diagnosis: '', treatment: '', prescription: '', blood_pressure: '', weight_kg: '', follow_up: '' });
const patientForm = reactive(blankPatient());
const appointmentForm = reactive(blankAppointment());
const recordForm = reactive(blankRecord());

function localDateTime() {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
}
function notify(message, type = 'success') {
    toast.value = { show: true, message, type };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value.show = false; }, 3300);
}
function friendlyError(error) {
    if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {};
        return 'Periksa kembali data yang wajib diisi.';
    }
    return error.response?.data?.message || 'Terjadi kendala saat menghubungi server. Pastikan Laravel dan MySQL berjalan.';
}
async function loadDashboard() { const r = await axios.get('/api/dashboard'); dashboard.value = r.data; }
async function loadPatients() { const r = await axios.get('/api/pasiens'); patients.value = r.data.data || []; }
async function loadAppointments() { const params = {}; if (dateFilter.value) params.date = dateFilter.value; if (appointmentFilter.value) params.status = appointmentFilter.value; const r = await axios.get('/api/appointments', { params }); appointments.value = r.data.data || []; }
async function loadDoctors() { const r = await axios.get('/api/doctors'); doctors.value = r.data.data || []; }
async function loadRecords() { const r = await axios.get('/api/records'); records.value = r.data.data || []; }
async function loadReport() { const r = await axios.get('/api/reports'); report.value = r.data; }
async function refreshAll() {
    loading.value = true;
    try { await Promise.all([loadDashboard(), loadPatients(), loadAppointments(), loadDoctors(), loadRecords(), loadReport()]); }
    catch (error) { notify(friendlyError(error), 'error'); }
    finally { loading.value = false; }
}
const filteredPatients = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('id-ID');
    if (!term) return patients.value;
    return patients.value.filter(p => [p.Nama_Pasien, p.No_Hp, p.kode_pasien, p.Alamat_Pasien].some(v => String(v || '').toLocaleLowerCase('id-ID').includes(term)));
});
const filteredAppointments = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('id-ID');
    if (!term) return appointments.value;
    return appointments.value.filter(a => [a.patient?.Nama_Pasien, a.doctor?.name, a.service_name, a.status].some(v => String(v || '').toLocaleLowerCase('id-ID').includes(term)));
});
const navItems = [
    { id: 'dashboard', label: 'Overview', sub: 'Ringkasan operasional', icon: '▦' },
    { id: 'patients', label: 'Patients', sub: 'Direktori pasien', icon: '♙' },
    { id: 'appointments', label: 'Appointments', sub: 'Jadwal & antrean', icon: '▣' },
    { id: 'doctors', label: 'Medical team', sub: 'Dokter & spesialisasi', icon: '✚' },
    { id: 'records', label: 'Consultations', sub: 'Catatan kunjungan', icon: '▤' },
    { id: 'reports', label: 'Reports', sub: 'Analitik klinik', icon: '◫' },
];
const currentNav = computed(() => navItems.find(item => item.id === view.value) || navItems[0]);
function changeView(id) { view.value = id; search.value = ''; if (id === 'appointments') loadAppointments(); }
function clearErrors() { errors.value = {}; }
function openPatient(patient = null) {
    editingId.value = patient?.id ?? null; Object.assign(patientForm, patient ? { ...blankPatient(), ...patient, tanggal_lahir: patient.tanggal_lahir?.slice(0, 10) || '' } : blankPatient());
    clearErrors(); modal.value = 'patient';
}
function openAppointment() { Object.assign(appointmentForm, blankAppointment()); clearErrors(); modal.value = 'appointment'; }
function openRecord() { Object.assign(recordForm, blankRecord()); clearErrors(); modal.value = 'record'; }
function closeModal() { if (busy.value) return; modal.value = ''; clearErrors(); }
async function savePatient() {
    busy.value = true; clearErrors();
    try { const payload = { ...patientForm }; if (editingId.value) await axios.put(`/api/pasiens/${editingId.value}`, payload); else await axios.post('/api/pasiens', payload);
        notify(editingId.value ? 'Profil pasien diperbarui.' : 'Pasien berhasil didaftarkan.'); modal.value = ''; await Promise.all([loadPatients(), loadDashboard()]);
    } catch (error) { notify(friendlyError(error), 'error'); } finally { busy.value = false; }
}
async function saveAppointment() {
    busy.value = true; clearErrors();
    try { await axios.post('/api/appointments', { ...appointmentForm }); notify('Janji temu berhasil dibuat.'); modal.value = ''; await Promise.all([loadAppointments(), loadDashboard(), loadReport()]); }
    catch (error) { notify(friendlyError(error), 'error'); } finally { busy.value = false; }
}
async function changeStatus(appointment, status) {
    try { await axios.put(`/api/appointments/${appointment.id}`, { status }); notify('Status kunjungan diperbarui.'); await Promise.all([loadAppointments(), loadDashboard(), loadReport()]); }
    catch (error) { notify(friendlyError(error), 'error'); await loadAppointments(); }
}
async function removeAppointment(appointment) {
    if (!window.confirm(`Hapus jadwal ${appointment.patient?.Nama_Pasien || 'pasien'}?`)) return;
    try { await axios.delete(`/api/appointments/${appointment.id}`); notify('Janji temu dihapus.'); await Promise.all([loadAppointments(), loadDashboard(), loadReport()]); }
    catch (error) { notify(friendlyError(error), 'error'); }
}
async function saveRecord() {
    busy.value = true; clearErrors();
    try { await axios.post('/api/records', { ...recordForm }); notify('Catatan konsultasi berhasil disimpan.'); modal.value = ''; await Promise.all([loadRecords(), loadAppointments(), loadDashboard(), loadReport()]); }
    catch (error) { notify(friendlyError(error), 'error'); } finally { busy.value = false; }
}
function initials(name) { return String(name || 'P').trim().split(/\s+/).slice(0, 2).map(x => x[0]?.toUpperCase()).join('') || 'P'; }
function formatDate(value, withTime = false) { if (!value) return '—'; const date = new Date(value); if (Number.isNaN(date.getTime())) return '—'; return new Intl.DateTimeFormat('id-ID', withTime ? { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' } : { day: '2-digit', month: 'short', year: 'numeric' }).format(date); }
function statusClass(status) { return status === 'Selesai' ? 'success' : status === 'Dibatalkan' ? 'danger' : status === 'Diperiksa' ? 'info' : 'warning'; }
function showError(field) { return errors.value[field]?.[0] || ''; }
function exportPatients() {
    const headers = ['Kode pasien','Nama','No HP','Jenis kelamin','Tanggal lahir','Golongan darah','Alamat'];
    const rows = patients.value.map(p => [p.kode_pasien,p.Nama_Pasien,p.No_Hp,p.jenis_kelamin,p.tanggal_lahir,p.golongan_darah,p.Alamat_Pasien]);
    const csv = [headers, ...rows].map(row => row.map(v => `"${String(v ?? '').replaceAll('"','""')}"`).join(',')).join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' }); const url = URL.createObjectURL(blob);
    const link = document.createElement('a'); link.href = url; link.download = 'direktori-pasien.csv'; link.click(); URL.revokeObjectURL(url);
}
onMounted(refreshAll);
</script>

<template>
<div class="app-shell">
    <aside class="sidebar" :class="{ 'sidebar-open': false }">
        <a class="brand" href="#" @click.prevent="changeView('dashboard')"><span class="brand-mark">✚</span><span><b>CareDesk</b><small>CLINIC WORKSPACE</small></span></a>
        <div class="side-label">WORKSPACE</div>
        <nav class="side-nav">
            <button v-for="item in navItems" :key="item.id" :class="['nav-link', { active: view === item.id }]" @click="changeView(item.id)"><span class="nav-icon">{{ item.icon }}</span><span>{{ item.label }}<small>{{ item.sub }}</small></span><i v-if="view === item.id"></i></button>
        </nav>
        <div class="sidebar-bottom"><div class="secure-note"><span>✓</span><div><b>Local environment</b><small>Laravel API connected to MySQL</small></div></div><p>CareDesk · Demo workspace</p></div>
    </aside>

    <main class="main-area">
        <header class="topbar"><div class="mobile-brand"><span class="brand-mark">✚</span><b>CareDesk</b></div><div class="crumb"><span>Clinic workspace</span><b>/</b><strong>{{ currentNav.label }}</strong></div><div class="top-actions"><span class="system-pill"><i></i> System online</span><button class="icon-button" title="Muat ulang data" @click="refreshAll">⟳</button><span class="avatar-small">CD</span></div></header>
        <div class="content-wrap">
            <section v-if="view === 'dashboard'">
                <div class="page-heading"><div><div class="eyebrow">{{ new Intl.DateTimeFormat('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'}).format(new Date()) }}</div><h1>Good day, clinic team<span>.</span></h1><p>Berikut ringkasan aktivitas dan hal yang perlu ditangani hari ini.</p></div><button class="btn btn-primary" @click="openAppointment">＋ New appointment</button></div>
                <div class="stats-grid">
                    <article class="stat-card"><div class="stat-top"><span>Total patients</span><i class="stat-icon blue">♙</i></div><strong>{{ loading ? '—' : (dashboard.stats.patients ?? 0).toLocaleString('id-ID') }}</strong><small>Profil terdaftar</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Appointments today</span><i class="stat-icon violet">▣</i></div><strong>{{ loading ? '—' : dashboard.stats.today ?? 0 }}</strong><small>Agenda hari ini</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Waiting queue</span><i class="stat-icon amber">◷</i></div><strong>{{ loading ? '—' : dashboard.stats.waiting ?? 0 }}</strong><small>Menunggu layanan</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Completed visits</span><i class="stat-icon green">✓</i></div><strong>{{ loading ? '—' : dashboard.stats.completed ?? 0 }}</strong><small>Selesai hari ini</small></article>
                </div>
                <div class="dashboard-grid">
                    <section class="panel"><div class="panel-heading"><div><h2>Today's schedule</h2><p>Urutan janji temu yang terdaftar hari ini</p></div><button class="text-action" @click="changeView('appointments')">View schedule →</button></div>
                        <div v-if="dashboard.todayAppointments?.length" class="schedule-list"><article v-for="a in dashboard.todayAppointments" :key="a.id" class="schedule-row"><div class="time-block"><b>{{ new Date(a.scheduled_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) }}</b><small>WIB</small></div><div class="schedule-person"><span class="patient-avatar">{{ initials(a.patient?.Nama_Pasien) }}</span><div><b>{{ a.patient?.Nama_Pasien }}</b><small>{{ a.service_name }} · {{ a.doctor?.name }}</small></div></div><span :class="['status-badge',statusClass(a.status)]">{{ a.status }}</span></article></div>
                        <div v-else class="empty-state"><span>▣</span><b>No appointments today</b><p>Jadwalkan kunjungan pertama untuk mengisi agenda.</p></div>
                    </section>
                    <section class="panel activity-panel"><div class="panel-heading"><div><h2>Activity overview</h2><p>Jumlah janji temu · 7 hari terakhir</p></div><span class="mini-tag">7 DAYS</span></div><div class="bar-chart"><div v-for="day in dashboard.week" :key="day.date" class="bar-column"><b>{{ day.count }}</b><div class="bar-track"><span :style="{height: `${Math.max(5, day.count / Math.max(1,...dashboard.week.map(x=>x.count)) * 100)}%`}"></span></div><small>{{ day.label }}</small></div></div><div class="chart-legend"><i></i> Janji temu tercatat</div><div class="recent-divider"></div><h3 class="small-section-title">Recent consultation notes</h3><div v-if="dashboard.recentRecords?.length" class="recent-list"><div v-for="r in dashboard.recentRecords" :key="r.id" class="recent-row"><div><b>{{ r.patient?.Nama_Pasien }}</b><small>{{ r.diagnosis }}</small></div><span>{{ formatDate(r.visited_at) }}</span></div></div><p v-else class="muted-empty">Belum ada catatan konsultasi.</p></section>
                </div>
                <div class="quick-grid"><button class="quick-card" @click="openPatient()"><span class="quick-icon">＋</span><b>Register a patient</b><small>Buat profil pasien baru</small><span class="quick-arrow">↗</span></button><button class="quick-card" @click="openRecord()"><span class="quick-icon">▤</span><b>Record consultation</b><small>Dokumentasikan kunjungan</small><span class="quick-arrow">↗</span></button><button class="quick-card" @click="changeView('reports')"><span class="quick-icon">◫</span><b>Explore reports</b><small>Lihat performa operasional</small><span class="quick-arrow">↗</span></button></div>
            </section>

            <section v-else-if="view === 'patients'">
                <div class="page-heading"><div><div class="eyebrow">PATIENT DIRECTORY</div><h1>Patient records<span>.</span></h1><p>Kelola profil pasien dan informasi dasar dalam satu tempat.</p></div><div class="heading-actions"><button class="btn btn-secondary" @click="exportPatients">↓ Export CSV</button><button class="btn btn-primary" @click="openPatient()">＋ Add patient</button></div></div>
                <div class="panel table-panel"><div class="table-toolbar"><div class="search-box"><span>⌕</span><input v-model="search" placeholder="Cari nama, kode, atau nomor HP..."></div><span class="result-count">{{ filteredPatients.length }} patients</span></div><div class="table-scroll"><table><thead><tr><th>PATIENT</th><th>CONTACT</th><th>PROFILE</th><th>ADDRESS</th><th>ACTIVITY</th><th></th></tr></thead><tbody><tr v-for="p in filteredPatients" :key="p.id"><td><div class="table-person"><span class="patient-avatar">{{ initials(p.Nama_Pasien) }}</span><div><b>{{ p.Nama_Pasien }}</b><small>{{ p.kode_pasien || `CD-${p.id}` }}</small></div></div></td><td>{{ p.No_Hp }}</td><td><span>{{ p.jenis_kelamin || '—' }}</span><small class="cell-sub">Gol. darah {{ p.golongan_darah || '—' }}</small></td><td class="address-cell">{{ p.Alamat_Pasien }}</td><td><span class="activity-count">{{ p.appointments_count || 0 }} visits</span></td><td><button class="row-action" @click="openPatient(p)">Edit</button></td></tr><tr v-if="!filteredPatients.length"><td colspan="6"><div class="empty-state compact"><b>Tidak ada pasien ditemukan</b><p>Coba kata kunci lain atau daftarkan pasien baru.</p></div></td></tr></tbody></table></div></div>
            </section>

            <section v-else-if="view === 'appointments'">
                <div class="page-heading"><div><div class="eyebrow">SCHEDULING & QUEUE</div><h1>Appointments<span>.</span></h1><p>Atur waktu kunjungan dan pantau progres antrean.</p></div><button class="btn btn-primary" @click="openAppointment">＋ Schedule visit</button></div>
                <div class="panel table-panel"><div class="table-toolbar wrap-toolbar"><div class="search-box"><span>⌕</span><input v-model="search" placeholder="Cari pasien, dokter, layanan..."></div><div class="filter-controls"><input type="date" v-model="dateFilter" @change="loadAppointments"><select v-model="appointmentFilter" @change="loadAppointments"><option value="">All statuses</option><option>Menunggu</option><option>Diperiksa</option><option>Selesai</option><option>Dibatalkan</option></select><button class="filter-reset" @click="dateFilter='';appointmentFilter='';search='';loadAppointments()">Reset</button></div></div><div class="table-scroll"><table><thead><tr><th>DATE & TIME</th><th>PATIENT</th><th>DOCTOR / SERVICE</th><th>REASON</th><th>STATUS</th><th>UPDATE</th></tr></thead><tbody><tr v-for="a in filteredAppointments" :key="a.id"><td><b>{{ formatDate(a.scheduled_at) }}</b><small class="cell-sub">{{ new Date(a.scheduled_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) }} WIB</small></td><td><b>{{ a.patient?.Nama_Pasien }}</b><small class="cell-sub">{{ a.patient?.No_Hp }}</small></td><td><b>{{ a.doctor?.name }}</b><small class="cell-sub">{{ a.service_name }}</small></td><td class="address-cell">{{ a.reason || '—' }}</td><td><span :class="['status-badge',statusClass(a.status)]">{{ a.status }}</span></td><td><div class="inline-actions"><select :value="a.status" @change="changeStatus(a,$event.target.value)" class="status-select"><option>Menunggu</option><option>Diperiksa</option><option>Selesai</option><option>Dibatalkan</option></select><button class="delete-action" title="Hapus jadwal" @click="removeAppointment(a)">×</button></div></td></tr><tr v-if="!filteredAppointments.length"><td colspan="6"><div class="empty-state compact"><b>Tidak ada jadwal yang sesuai</b><p>Ubah filter atau buat janji temu baru.</p></div></td></tr></tbody></table></div></div>
            </section>

            <section v-else-if="view === 'doctors'">
                <div class="page-heading"><div><div class="eyebrow">MEDICAL TEAM</div><h1>Care team<span>.</span></h1><p>Kenali tenaga medis, spesialisasi, dan aktivitas kunjungannya.</p></div><span class="count-chip">{{ doctors.length }} practitioners</span></div>
                <div class="doctor-grid"><article v-for="d in doctors" :key="d.id" class="doctor-card"><div class="doctor-card-top"><span class="doctor-avatar">{{ initials(d.name) }}</span><span :class="['status-badge',d.is_active?'success':'muted']">{{ d.is_active?'Active':'Inactive' }}</span></div><h2>{{ d.name }}</h2><p class="specialty">{{ d.specialty }}</p><div class="doctor-meta"><div><small>ROOM</small><b>{{ d.room || '—' }}</b></div><div><small>APPOINTMENTS</small><b>{{ d.appointments_count || 0 }}</b></div><div><small>NOTES</small><b>{{ d.medical_records_count || 0 }}</b></div></div></article><div v-if="!doctors.length" class="empty-state"><b>Belum ada data dokter.</b></div></div>
            </section>

            <section v-else-if="view === 'records'">
                <div class="page-heading"><div><div class="eyebrow">CLINICAL DOCUMENTATION</div><h1>Consultation notes<span>.</span></h1><p>Riwayat dokumentasi keluhan, catatan dokter, dan tindak lanjut.</p></div><button class="btn btn-primary" @click="openRecord">＋ New consultation</button></div>
                <div class="record-list"><article v-for="r in records" :key="r.id" class="record-card"><div class="record-head"><div class="table-person"><span class="patient-avatar">{{ initials(r.patient?.Nama_Pasien) }}</span><div><b>{{ r.patient?.Nama_Pasien }}</b><small>{{ formatDate(r.visited_at,true) }} · {{ r.doctor?.name }}</small></div></div><span v-if="r.blood_pressure" class="vital-chip">BP {{ r.blood_pressure }}</span></div><div class="record-body"><div><label>COMPLAINT</label><p>{{ r.complaint }}</p></div><div><label>CLINICAL NOTE</label><p>{{ r.diagnosis }}</p></div><div v-if="r.treatment"><label>TREATMENT / EDUCATION</label><p>{{ r.treatment }}</p></div><div v-if="r.follow_up"><label>FOLLOW-UP</label><p>{{ r.follow_up }}</p></div></div><div v-if="r.prescription" class="prescription-note"><b>Recommendation:</b> {{ r.prescription }}</div></article><div v-if="!records.length" class="empty-state panel"><b>Belum ada catatan konsultasi.</b><p>Mulai dokumentasi dari janji temu atau buat catatan baru.</p></div></div>
            </section>

            <section v-else-if="view === 'reports'">
                <div class="page-heading"><div><div class="eyebrow">CLINIC INSIGHTS</div><h1>Operational reports<span>.</span></h1><p>Ringkasan performa kunjungan untuk {{ report.month || 'bulan ini' }}.</p></div><span class="count-chip">MONTHLY OVERVIEW</span></div>
                <div class="stats-grid report-stats"><article class="stat-card"><div class="stat-top"><span>Total appointments</span><i class="stat-icon blue">▣</i></div><strong>{{ report.total ?? 0 }}</strong><small>Bulan berjalan</small></article><article class="stat-card"><div class="stat-top"><span>Completed</span><i class="stat-icon green">✓</i></div><strong>{{ report.completed ?? 0 }}</strong><small>Kunjungan selesai</small></article><article class="stat-card"><div class="stat-top"><span>Completion rate</span><i class="stat-icon violet">◫</i></div><strong>{{ report.completionRate ?? 0 }}%</strong><small>Rasio penyelesaian</small></article></div>
                <div class="report-grid"><section class="panel"><div class="panel-heading"><div><h2>Appointment trend</h2><p>Aktivitas 7 hari terakhir</p></div></div><div class="report-chart"><div v-for="d in report.daily" :key="d.label" class="report-bar"><b>{{ d.total }}</b><div><i :style="{height:`${Math.max(5,d.total/Math.max(1,...report.daily.map(x=>x.total))*100)}%`}"></i></div><small>{{ d.label }}</small></div></div></section><section class="panel"><div class="panel-heading"><div><h2>Popular services</h2><p>Distribusi jenis layanan bulan ini</p></div></div><div v-if="report.byService?.length" class="service-list"><div v-for="s in report.byService" :key="s.service_name" class="service-row"><div><b>{{ s.service_name }}</b><small>{{ s.total }} visits</small></div><span class="service-track"><i :style="{width:`${s.total/Math.max(1,...report.byService.map(x=>x.total))*100}%`} "></i></span></div></div><div v-else class="muted-empty">Belum ada data layanan bulan ini.</div></section></div>
            </section>
        </div>
        <footer class="app-footer"><span>CareDesk © {{ new Date().getFullYear() }}</span><span>Demo workspace · Gunakan data fiktif</span></footer>
    </main>

    <div v-if="modal" class="modal-backdrop" @click.self="closeModal">
        <section class="modal-card"><div class="modal-header"><div><span class="eyebrow">{{ modal==='patient'?'PATIENT PROFILE':modal==='appointment'?'SCHEDULING':'CLINICAL DOCUMENTATION' }}</span><h2>{{ modal==='patient'?(editingId?'Edit patient':'Register patient'):modal==='appointment'?'Schedule an appointment':'New consultation note' }}</h2></div><button class="modal-close" @click="closeModal">×</button></div>
            <form v-if="modal==='patient'" class="modal-form" @submit.prevent="savePatient"><div class="form-grid"><div class="form-field full"><label>Nama lengkap *</label><input v-model="patientForm.Nama_Pasien" required placeholder="Nama pasien"><small v-if="showError('Nama_Pasien')">{{ showError('Nama_Pasien') }}</small></div><div class="form-field"><label>Nomor HP *</label><input v-model="patientForm.No_Hp" required placeholder="08xxxxxxxxxx"><small v-if="showError('No_Hp')">{{ showError('No_Hp') }}</small></div><div class="form-field"><label>Jenis kelamin</label><select v-model="patientForm.jenis_kelamin"><option value="">Pilih</option><option>Laki-laki</option><option>Perempuan</option></select></div><div class="form-field"><label>Tanggal lahir</label><input type="date" v-model="patientForm.tanggal_lahir" :max="new Date().toISOString().slice(0,10)"></div><div class="form-field"><label>Golongan darah</label><select v-model="patientForm.golongan_darah"><option value="">Belum diketahui</option><option>A</option><option>B</option><option>AB</option><option>O</option></select></div><div class="form-field full"><label>Alamat *</label><textarea v-model="patientForm.Alamat_Pasien" required rows="2" placeholder="Alamat lengkap"></textarea><small v-if="showError('Alamat_Pasien')">{{ showError('Alamat_Pasien') }}</small></div><div class="form-field full"><label>Catatan alergi</label><textarea v-model="patientForm.alergi" rows="2" placeholder="Opsional"></textarea></div></div><div class="modal-actions"><button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button><button class="btn btn-primary" :disabled="busy">{{ busy?'Saving...':'Save patient' }}</button></div></form>
            <form v-else-if="modal==='appointment'" class="modal-form" @submit.prevent="saveAppointment"><div class="form-grid"><div class="form-field full"><label>Pasien *</label><select v-model="appointmentForm.pasien_id" required><option value="">Pilih pasien</option><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.Nama_Pasien }} · {{ p.No_Hp }}</option></select><small v-if="showError('pasien_id')">{{ showError('pasien_id') }}</small></div><div class="form-field full"><label>Dokter *</label><select v-model="appointmentForm.doctor_id" required><option value="">Pilih dokter</option><option v-for="d in doctors.filter(x=>x.is_active)" :key="d.id" :value="d.id">{{ d.name }} · {{ d.specialty }}</option></select><small v-if="showError('doctor_id')">{{ showError('doctor_id') }}</small></div><div class="form-field"><label>Jenis layanan *</label><select v-model="appointmentForm.service_name"><option>Konsultasi umum</option><option>Pemeriksaan rutin</option><option>Konsultasi anak</option><option>Pemeriksaan gigi</option><option>Kontrol lanjutan</option><option>Pemeriksaan laboratorium</option></select></div><div class="form-field"><label>Tanggal dan waktu *</label><input type="datetime-local" v-model="appointmentForm.scheduled_at" :min="localDateTime()" required><small v-if="showError('scheduled_at')">{{ showError('scheduled_at') }}</small></div><div class="form-field full"><label>Alasan kunjungan</label><textarea v-model="appointmentForm.reason" rows="2" placeholder="Keluhan atau tujuan kunjungan"></textarea></div></div><div class="modal-actions"><button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button><button class="btn btn-primary" :disabled="busy">{{ busy?'Saving...':'Create appointment' }}</button></div></form>
            <form v-else class="modal-form" @submit.prevent="saveRecord"><div class="form-grid"><div class="form-field"><label>Pasien *</label><select v-model="recordForm.pasien_id" required><option value="">Pilih pasien</option><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.Nama_Pasien }}</option></select></div><div class="form-field"><label>Dokter *</label><select v-model="recordForm.doctor_id" required><option value="">Pilih dokter</option><option v-for="d in doctors.filter(x=>x.is_active)" :key="d.id" :value="d.id">{{ d.name }}</option></select></div><div class="form-field"><label>Waktu konsultasi *</label><input type="datetime-local" v-model="recordForm.visited_at" required></div><div class="form-field"><label>Janji temu terkait</label><select v-model="recordForm.appointment_id"><option value="">Tidak terkait</option><option v-for="a in appointments.filter(x=>['Diperiksa','Selesai'].includes(x.status))" :key="a.id" :value="a.id">{{ formatDate(a.scheduled_at,true) }} · {{ a.patient?.Nama_Pasien }}</option></select></div><div class="form-field"><label>Tekanan darah</label><input v-model="recordForm.blood_pressure" placeholder="120/80"></div><div class="form-field"><label>Berat badan (kg)</label><input type="number" min="1" max="400" step="0.01" v-model="recordForm.weight_kg"></div><div class="form-field full"><label>Keluhan utama *</label><textarea v-model="recordForm.complaint" required rows="2"></textarea></div><div class="form-field full"><label>Diagnosis / catatan klinis *</label><textarea v-model="recordForm.diagnosis" required rows="2"></textarea></div><div class="form-field full"><label>Tindakan / edukasi</label><textarea v-model="recordForm.treatment" rows="2"></textarea></div><div class="form-field full"><label>Resep / rekomendasi</label><textarea v-model="recordForm.prescription" rows="2"></textarea></div><div class="form-field full"><label>Rencana tindak lanjut</label><textarea v-model="recordForm.follow_up" rows="2"></textarea></div></div><div class="modal-actions"><button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button><button class="btn btn-primary" :disabled="busy">{{ busy?'Saving...':'Save consultation' }}</button></div></form>
        </section>
    </div>
    <transition name="toast"><div v-if="toast.show" :class="['toast-message',toast.type]">{{ toast.message }}</div></transition>
</div>
</template>
