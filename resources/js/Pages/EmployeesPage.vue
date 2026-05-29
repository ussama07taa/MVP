<template>
<div class="min-h-screen bg-[#f1f5f9] p-6 lg:p-10">
  <!-- Header -->
  <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-8">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-xl shadow-amber-500/30">
        <HardHatIcon class="w-7 h-7 text-white" />
      </div>
      <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Équipe &amp; <span class="text-amber-600">Paie</span></h1>
        <p class="text-slate-500 font-bold text-sm">Pointage, avances et salaires — Vue par mois.</p>
      </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
      <!-- Actualiser Button (Master UX) -->
      <button @click="loadEmployees" 
        :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
        class="group relative p-2.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-300 active:scale-90"
        title="Actualiser">
        <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-4 h-4 text-amber-600 transition-transform duration-500" />
      </button>

      <div class="relative">
        <SearchIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input v-model="searchQuery" type="text" placeholder="Chercher..."
          class="pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 w-48">
      </div>
      <input type="month" v-model="selectedMonth" @change="loadEmployees"
        class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
      <Link href="/admin/attendance" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-sm shadow-lg shadow-indigo-600/20 hover:-translate-y-0.5 active:scale-95 transition-all flex items-center gap-2">
        <CalendarCheckIcon class="w-4 h-4" /> Pointage
      </Link>
      <button @click="openAddForm" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl font-black text-sm shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all flex items-center gap-2">
        <UserPlusIcon class="w-4 h-4" /> Nouveau
      </button>
    </div>
  </header>

  <!-- KPI Row -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div v-for="kpi in kpis" :key="kpi.label" class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center" :style="`background:${kpi.bg}`">
        <component :is="kpi.icon" class="w-5 h-5" :style="`color:${kpi.color}`" />
      </div>
      <div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</p>
        <p class="text-xl font-black text-slate-900">{{ kpi.value }}</p>
      </div>
    </div>
  </div>

  <!-- Add/Edit Form -->
  <div v-if="showForm" class="bg-white rounded-2xl border border-slate-100 shadow-lg p-8 mb-8">
    <h2 class="text-xl font-black text-slate-900 mb-6">{{ form.id ? '✏️ Modifier' : '➕ Nouvel Employé' }}</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <input v-model="form.name" placeholder="Nom complet *" class="inp">
      <select v-model="form.role" class="inp">
        <option>Menuisier</option><option>Peintre</option><option>Manoeuvre</option>
        <option>Chauffeur</option><option>Chef d'Atelier</option><option>Électricien</option>
      </select>
      <input v-model="form.phone" placeholder="Téléphone" class="inp">
      <input v-model="form.daily_salary" type="number" placeholder="Salaire/Jour" class="inp">
    </div>
    <div class="flex justify-end gap-3 mt-6">
      <button @click="showForm=false" class="px-6 py-2.5 text-slate-500 font-bold">Annuler</button>
      <button @click="saveEmployee" class="px-8 py-2.5 bg-amber-500 text-white rounded-xl font-black shadow-lg">ENREGISTRER</button>
    </div>
  </div>

  <!-- Data Table -->
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div v-if="isLoading" class="p-20 text-center"><div class="w-10 h-10 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
    <div v-else class="overflow-x-auto w-full">
      <table class="w-full text-left">
      <thead>
        <tr class="bg-slate-50/80 border-b border-slate-100 text-[10px] uppercase tracking-widest text-slate-400 font-black">
          <th class="p-4">Employé</th>
          <th class="p-4 text-center hidden sm:table-cell">Jours Travaillés</th>
          <th class="p-4 text-right hidden md:table-cell">Salaire Gagné</th>
          <th class="p-4 text-right hidden md:table-cell">Avances (Salaf)</th>
          <th class="p-4 text-right">Net à Payer</th>
          <th class="p-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="emp in filteredEmployees" :key="emp.id" class="border-b border-slate-50 hover:bg-amber-50/30 transition-colors group">
          <td class="p-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-black text-white shadow-sm" :style="`background:${avatarColor(emp.name)}`">
                {{ emp.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="font-black text-slate-800 text-sm">{{ emp.name }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ emp.role }} · {{ emp.daily_salary }} DH/j</p>
              </div>
            </div>
          </td>
          <td class="p-4 text-center hidden sm:table-cell">
            <span class="font-black text-slate-700 text-lg">{{ emp.worked_days || 0 }}</span>
            <span class="text-[10px] text-slate-400 font-bold ml-1">jours</span>
            <div class="text-[9px] text-slate-400 mt-0.5">{{ emp.present_days || 0 }}✅ {{ emp.half_days || 0 }}⚡</div>
          </td>
          <td class="p-4 text-right font-black text-slate-700 hidden md:table-cell">{{ fmt(emp.earned_salary) }} DH</td>
          <td class="p-4 text-right font-black text-rose-500 hidden md:table-cell">- {{ fmt(emp.total_advances) }} DH</td>
          <td class="p-4 text-right">
            <span class="font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg text-sm">{{ fmt(emp.net_to_pay) }} DH</span>
          </td>
          <td class="p-4">
            <div class="flex justify-center gap-1.5">
              <button @click="openAdjustment(emp)" class="px-3 py-1.5 bg-amber-50 text-amber-600 rounded-lg text-[10px] font-black uppercase hover:bg-amber-100 transition-all flex items-center gap-1 group/btn" :title="Number(emp.total_advances) > Number(emp.earned_salary) ? 'Dette élevée !' : ''">
                <AlertTriangleIcon v-if="Number(emp.total_advances) > Number(emp.earned_salary)" class="w-3 h-3 text-rose-500 animate-pulse" />
                <BanknoteIcon class="w-3 h-3" /> Ajuster
              </button>
              <button @click="payEmp(emp)" class="px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-black uppercase hover:bg-indigo-100 transition-all">
                <CheckCircle2Icon class="w-3 h-3 inline mr-1" />Payer
              </button>
              <button @click="openHistory(emp)" class="w-7 h-7 bg-slate-50 text-slate-400 rounded-lg hover:bg-slate-100 flex items-center justify-center">
                <HistoryIcon class="w-3.5 h-3.5" />
              </button>
              <button @click="editEmployee(emp)" class="w-7 h-7 text-slate-400 hover:text-amber-600 flex items-center justify-center">
                <PencilIcon class="w-3.5 h-3.5" />
              </button>
              <button @click="deleteEmployee(emp)" class="w-7 h-7 text-slate-300 hover:text-rose-500 flex items-center justify-center">
                <Trash2Icon class="w-3.5 h-3.5" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
      <!-- Totals Footer -->
      <tfoot v-if="filteredEmployees.length">
        <tr class="bg-slate-900 text-white text-sm">
          <td class="p-4 font-black">TOTAL ({{ filteredEmployees.length }} employés)</td>
          <td class="p-4 text-center font-black hidden sm:table-cell">{{ totalDays }} jours</td>
          <td class="p-4 text-right font-black hidden md:table-cell">{{ fmt(totalEarned) }} DH</td>
          <td class="p-4 text-right font-black text-rose-300 hidden md:table-cell">- {{ fmt(totalAdvances) }} DH</td>
          <td class="p-4 text-right font-black text-emerald-300">{{ fmt(totalNet) }} DH</td>
          <td class="p-4"></td>
        </tr>
      </tfoot>
    </table>
    </div>
    <div v-if="!isLoading && filteredEmployees.length === 0" class="p-16 text-center text-slate-300">
      <UsersIcon class="w-12 h-12 mx-auto mb-4 opacity-30" />
      <p class="font-black text-sm uppercase tracking-widest">Aucun employé trouvé</p>
    </div>
  </div>

<Teleport to="body">

  <!-- Adjustment Modal -->
  <div v-if="adjustmentEmp" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="adjustmentEmp=null">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center" :class="adjType === 'bonus' ? 'bg-emerald-50 text-emerald-600' : adjType === 'sanction' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'">
          <component :is="adjType === 'bonus' ? TrendingUpIcon : adjType === 'sanction' ? TrendingDownIcon : BanknoteIcon" class="w-6 h-6" />
        </div>
        <div>
          <h3 class="text-xl font-black text-slate-900 leading-tight">Ajustement</h3>
          <p class="text-sm text-slate-500 font-bold">Pour : <span class="text-indigo-600">{{ adjustmentEmp.name }}</span></p>
        </div>
      </div>

      <div class="flex bg-slate-100 p-1.5 rounded-xl mb-6">
        <button v-for="t in ['advance', 'bonus', 'sanction']" :key="t" 
          @click="adjType = t"
          :class="adjType === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
          class="flex-1 py-2 rounded-lg text-xs font-black uppercase transition-all capitalize">
          {{ t === 'advance' ? 'Salaf' : t === 'bonus' ? 'Prime' : 'Sanction' }}
        </button>
      </div>

      <div class="relative mb-4">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-xl text-slate-300">DH</span>
        <input v-model="adjAmt" type="number" step="50" class="w-full pl-14 pr-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-2xl font-black text-slate-900 focus:border-amber-400 focus:ring-0" placeholder="0" autofocus>
      </div>

      <textarea v-model="adjNotes" placeholder="Notes / Raison (optionnel)..." class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:border-amber-400 focus:ring-0 mb-6" rows="2"></textarea>

      <div class="flex gap-3">
        <button @click="adjustmentEmp=null" class="flex-1 py-4 text-slate-500 font-black">ANNULER</button>
        <button @click="submitAdjustment" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl font-black shadow-xl hover:-translate-y-0.5 transition-all">VALIDER</button>
      </div>
    </div>
  </div>



  <!-- Toast -->
  <div v-if="successMsg" class="fixed top-6 left-1/2 -translate-x-1/2 z-[10000] bg-emerald-600 text-white px-6 py-3 rounded-xl font-black shadow-2xl flex items-center gap-2">
    <CheckCircle2Icon class="w-5 h-5" /> {{ successMsg }}
  </div>
  </Teleport>
</div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { usePage, router, Link } from '@inertiajs/vue3';
import { HardHatIcon, UserPlusIcon, SearchIcon, UsersIcon, BanknoteIcon, CheckCircle2Icon, PencilIcon, Trash2Icon, CalendarCheckIcon, HistoryIcon, RotateCwIcon, TrendingUpIcon, TrendingDownIcon, AlertTriangleIcon } from 'lucide-vue-next';

const employees = ref([]);
const isLoading = ref(true);
const showForm = ref(false);
const searchQuery = ref('');
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const form = ref({ id: null, name: '', role: 'Menuisier', phone: '', daily_salary: 0 });

// Adjustment Modal Refs
const adjustmentEmp = ref(null);
const adjAmt = ref('');
const adjType = ref('advance');
const adjNotes = ref('');

const showPointage = ref(false);
const pointageDate = ref(new Date().toISOString().split('T')[0]);
const attendancesForm = reactive({});
const historyEmp = ref(null);
const history = ref([]);
const historyLoading = ref(false);
const historyStart = ref('');
const historyEnd = ref('');
const successMsg = ref('');

const attOpts = [
  { val: 'present', label: '✅ Présent', active: 'bg-emerald-500 text-white border-emerald-500 shadow-lg' },
  { val: 'half_day', label: '⚡ ½ Jour', active: 'bg-amber-500 text-white border-amber-500 shadow-lg' },
  { val: 'absent', label: '❌ Absent', active: 'bg-rose-500 text-white border-rose-500 shadow-lg' },
];

const COLORS = ['#f59e0b','#6366f1','#10b981','#ef4444','#8b5cf6','#0ea5e9','#f43f5e','#14b8a6'];
const avatarColor = (n) => COLORS[(n || '').charCodeAt(0) % COLORS.length];

const filteredEmployees = computed(() => {
  const q = searchQuery.value.toLowerCase();
  return q ? employees.value.filter(e => e.name.toLowerCase().includes(q) || e.role.toLowerCase().includes(q)) : employees.value;
});

const totalDays = computed(() => filteredEmployees.value.reduce((s, e) => s + (e.worked_days || 0), 0));
const totalEarned = computed(() => filteredEmployees.value.reduce((s, e) => s + (e.earned_salary || 0), 0));
const totalAdvances = computed(() => filteredEmployees.value.reduce((s, e) => s + Number(e.total_advances || 0), 0));
const totalNet = computed(() => filteredEmployees.value.reduce((s, e) => s + (e.net_to_pay || 0), 0));

const kpis = computed(() => [
  { label: 'Employés', icon: UsersIcon, value: employees.value.length, bg: '#fff7ed', color: '#ea580c' },
  { label: 'Salaires Gagnés', icon: BanknoteIcon, value: fmt(totalEarned.value) + ' DH', bg: '#f0fdf4', color: '#16a34a' },
  { label: 'Total Avances', icon: BanknoteIcon, value: fmt(totalAdvances.value) + ' DH', bg: '#fef2f2', color: '#dc2626' },
  { label: 'Net à Payer', icon: CheckCircle2Icon, value: fmt(totalNet.value) + ' DH', bg: '#f5f3ff', color: '#7c3aed' },
]);

const showSuccess = (msg) => { successMsg.value = msg; setTimeout(() => successMsg.value = '', 3000); };
const fmt = (v) => { const n = parseFloat(v); return isNaN(n) ? '0.00' : n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

const loadEmployees = async () => {
  isLoading.value = true;
  try { employees.value = (await axios.get('/api/admin/employees', { params: { month: selectedMonth.value } })).data; }
  catch(e) {}
  finally { isLoading.value = false; }
};

const openAddForm = () => { form.value = { id: null, name: '', role: 'Menuisier', phone: '', daily_salary: 0 }; showForm.value = !showForm.value; };
const editEmployee = (emp) => { form.value = { id: emp.id, name: emp.name, role: emp.role, phone: emp.phone, daily_salary: emp.daily_salary }; showForm.value = true; };

const saveEmployee = async () => {
  if (!form.value.name) { toast.warning('Le nom est obligatoire.'); return; }
  const p = { name: form.value.name, role: form.value.role, phone: form.value.phone, daily_salary: form.value.daily_salary };
  if (form.value.id) await axios.put(`/api/admin/employees/${form.value.id}`, p);
  else await axios.post('/api/admin/employees', p);
  showForm.value = false; loadEmployees();
};

const deleteEmployee = async (emp) => {
  if (!confirm(`Supprimer ${emp.name} ? Son historique sera supprimé.`)) return;
  try { await axios.delete(`/api/admin/employees/${emp.id}`); loadEmployees(); }
  catch(e) { toast.error(e.response?.data?.error || 'Erreur'); }
};

const openAdjustment = (emp) => { 
  adjustmentEmp.value = emp; 
  adjAmt.value = ''; 
  adjType.value = 'advance';
  adjNotes.value = '';
};

const submitAdjustment = async () => {
  if (!adjAmt.value || adjAmt.value < 0) return;
  await axios.post(`/api/admin/employees/${adjustmentEmp.value.id}/advance`, { 
    amount: parseFloat(adjAmt.value),
    type: adjType.value,
    notes: adjNotes.value
  });
  adjustmentEmp.value = null; 
  showSuccess('Ajustement enregistré !'); 
  loadEmployees();
};

const payEmp = async (emp) => {
  const net = emp.net_to_pay || 0;
  if (!confirm(`✅ Payer ${fmt(net)} DH à ${emp.name} ?`)) return;
  try { await axios.post(`/api/admin/employees/${emp.id}/pay`); showSuccess(`💰 ${emp.name} payé !`); loadEmployees(); }
  catch(e) { toast.error(e.response?.data?.error || 'Erreur'); }
};

const openPointageModal = () => { showPointage.value = true; loadAttendances(); };
const loadAttendances = async () => {
  employees.value.forEach(e => attendancesForm[e.id] = 'present');
  try { (await axios.get(`/api/admin/attendances/${pointageDate.value}`)).data.forEach(a => attendancesForm[a.employee_id] = a.status); } catch(e) {}
};
const submitPointage = async () => {
  const payload = Object.keys(attendancesForm).map(id => ({ employee_id: id, status: attendancesForm[id] }));
  await axios.post('/api/admin/attendances', { date: pointageDate.value, attendances: payload });
  showPointage.value = false; showSuccess('Pointage enregistré !'); loadEmployees();
};

const openHistory = (emp) => {
  router.visit(`/admin/employees/${emp.id}`);
};
const deleteTransaction = async (item) => {
  if (!confirm('Supprimer cette transaction ?')) return;
  await axios.delete(`/api/admin/expenses/${item.id}`);
  if (historyEmp.value) openHistory(historyEmp.value); loadEmployees();
};

onMounted(loadEmployees);
</script>

<style scoped>
.inp { width:100%; background:#f8fafc; border:2px solid #f1f5f9; border-radius:0.75rem; padding:0.75rem 1rem; font-size:0.875rem; font-weight:700; color:#1e293b; outline:none; transition:all 0.3s; }
.inp:focus { background:white; border-color:#fbbf24; box-shadow:0 4px 15px -3px rgba(251,191,36,0.1); }
</style>
