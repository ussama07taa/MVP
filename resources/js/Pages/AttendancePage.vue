<template>
  <div class="max-w-7xl mx-auto pb-10 px-4">
    <!-- Header with Premium Design -->
    <header class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
      <div class="animate-in fade-in slide-in-from-left duration-700">
        <div class="flex items-center mb-1">
          <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mr-4">
            <CalendarCheckIcon class="w-7 h-7 text-white"/>
          </div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Pointage <span class="text-indigo-600">Quotidien</span></h1>
        </div>
        <p class="text-slate-400 font-medium ml-14 flex items-center">
           Enregistrez la présence de l'équipe pour le <span class="mx-1.5 font-black text-slate-700 uppercase">{{ formatDate(pointageDate) }}</span>
        </p>
      </div>

      <div class="flex items-center gap-4 animate-in fade-in slide-in-from-right duration-700">
          <div class="bg-white p-2 rounded-[22px] border border-slate-100 shadow-sm flex items-center">
             <CalendarIcon class="w-4 h-4 text-slate-400 ml-4 mr-2" />
             <input type="date" v-model="pointageDate" @change="loadAttendances" 
                class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer pr-6">
          </div>
          
          <button @click="markAllPresent" 
            class="px-6 py-4 bg-slate-900 text-white font-black rounded-2xl shadow-lg shadow-slate-200 hover:bg-slate-800 transition-all active:scale-95 flex items-center">
            <CheckCheckIcon class="w-5 h-5 mr-2" /> Tout Présent
          </button>
      </div>
    </header>

    <!-- Quick Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div v-for="stat in summaryStats" :key="stat.label" 
            class="bg-white rounded-[35px] p-6 shadow-sm border border-slate-50 flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center" :class="stat.bg">
                <component :is="stat.icon" class="w-6 h-6" :class="stat.color" />
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                <p class="text-2xl font-black text-slate-900">{{ stat.value }}</p>
            </div>
        </div>
    </div>

    <!-- Attendance Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom duration-1000">
        <div v-for="emp in employees" :key="emp.id" 
            class="bg-white rounded-[40px] p-8 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border-2 transition-all duration-300 relative overflow-hidden group"
            :class="[
                attendancesForm[emp.id] === 'present' ? 'border-emerald-100 bg-emerald-50/10' : 
                attendancesForm[emp.id] === 'half_day' ? 'border-amber-100 bg-amber-50/10' : 
                attendancesForm[emp.id] === 'absent' ? 'border-rose-100 bg-rose-50/10' : 'border-slate-50'
            ]">
            
            <div class="flex items-center mb-8 relative z-10">
                <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center font-black text-xl mr-5 group-hover:scale-110 transition-transform">
                    {{ emp.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-lg leading-tight">{{ emp.name }}</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ emp.role }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 relative z-10 mb-6">
                <button v-for="opt in attOpts" :key="opt.val" 
                    @click="attendancesForm[emp.id] = opt.val"
                    :class="[
                        attendancesForm[emp.id] === opt.val ? opt.active : 'bg-slate-50 text-slate-400 border-transparent grayscale'
                    ]"
                    class="flex flex-col items-center justify-center py-4 rounded-3xl border-2 transition-all duration-300 active:scale-90">
                    <component :is="opt.icon" class="w-6 h-6 mb-2" />
                    <span class="text-[10px] font-black uppercase tracking-widest">{{ opt.label }}</span>
                </button>
            </div>

            <!-- Overtime Input -->
            <div class="relative z-10 p-4 bg-slate-50 rounded-3xl border border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center">
                        <ZapIcon class="w-3 h-3 mr-1 text-amber-500" /> Heures Supp
                    </label>
                    <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg">Optionnel</span>
                </div>
                <div class="flex items-center">
                    <input type="number" v-model="overtimeForm[emp.id]" step="0.5" min="0" placeholder="0" 
                        class="w-full bg-white border-slate-200 rounded-xl py-2 px-4 text-sm font-black text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                    <span class="ml-3 text-xs font-black text-slate-400 uppercase">H</span>
                </div>
            </div>

            <!-- Subtle background indicator -->
            <div class="absolute -bottom-10 -right-10 opacity-5 group-hover:rotate-12 transition-transform duration-700">
                <component :is="attOpts.find(o => o.val === attendancesForm[emp.id])?.icon || UserIcon" class="w-32 h-32" />
            </div>
        </div>
    </div>

    <!-- Floating Save Bar -->
    <div class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 animate-in slide-in-from-bottom-20 duration-1000">
        <div class="flex items-center gap-3">
          <button @click="submitPointage" 
              :disabled="isSaving"
              class="px-10 py-5 bg-indigo-600 text-white font-black rounded-full shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95 flex items-center group">
              <SaveIcon v-if="!isSaving" class="w-5 h-5 mr-3 group-hover:rotate-12 transition-transform" />
              <RotateCwIcon v-else class="w-5 h-5 mr-3 animate-spin" />
              {{ isSaving ? 'Enregistrement...' : 'Enregistrer le Pointage' }}
          </button>
        </div>
    </div>

    <!-- Saved Attendances Table -->
    <div v-if="savedAttendances.length > 0" class="mt-20 bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden mb-20">
      <div class="px-10 py-6 border-b border-slate-50 flex items-center justify-between">
        <h3 class="font-black text-slate-700 text-sm uppercase tracking-widest">Pointage enregistré pour le {{ formatDate(pointageDate) }}</h3>
        <span class="text-xs text-slate-400 font-bold">{{ savedAttendances.length }} enregistrements</span>
      </div>
      <div class="overflow-x-auto w-full"><table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/50">
            <th class="px-10 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Employé</th>
            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Heures Supp</th>
            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Salaire Total</th>
            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="att in savedAttendances" :key="att.employee_id" class="hover:bg-slate-50/50 transition-colors group">
            <td class="px-10 py-5">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xs">
                  {{ getEmployeeName(att.employee_id).charAt(0).toUpperCase() }}
                </div>
                <span class="font-black text-slate-800">{{ getEmployeeName(att.employee_id) }}</span>
              </div>
            </td>
            <td class="px-6 py-5">
              <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border" :class="getStatusBadgeClass(att.status)">
                {{ att.status === 'present' ? 'Présent' : att.status === 'half_day' ? '1/2 Jour' : 'Absent' }}
              </span>
            </td>
            <td class="px-6 py-5 text-right font-black text-slate-500">
              {{ att.overtime_hours > 0 ? att.overtime_hours + ' H' : '-' }}
            </td>
            <td class="px-6 py-5 text-right font-bold text-emerald-600">
              {{ formatCurrency((parseFloat(att.wage_earned) + parseFloat(att.overtime_wage))) }} DH
            </td>
            <td class="px-6 py-5 text-right">
              <button @click="deleteAttendance(att.employee_id)" class="p-2 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition-all opacity-0 group-hover:opacity-100" title="Supprimer">
                <TrashIcon class="w-4 h-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
</template>



<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  CalendarCheckIcon, CalendarIcon, RotateCwIcon, 
  CheckCheckIcon, SaveIcon, UserIcon, CheckCircle2Icon, 
  ZapIcon, XCircleIcon, UsersIcon, TrashIcon
} from 'lucide-vue-next';

const isLoading = ref(true);
const isSaving = ref(false);
const employees = ref([]);
const savedAttendances = ref([]);
const pointageDate = ref(new Date().toISOString().split('T')[0]);
const attendancesForm = reactive({});
const overtimeForm = reactive({});

const attOpts = [
  { val: 'present', label: 'Présent', icon: CheckCircle2Icon, active: 'bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-100' },
  { val: 'half_day', label: '1/2 Jour', icon: ZapIcon, active: 'bg-amber-500 text-white border-amber-400 shadow-lg shadow-amber-100' },
  { val: 'absent', label: 'Absent', icon: XCircleIcon, active: 'bg-rose-500 text-white border-rose-400 shadow-lg shadow-rose-100' },
];

const summaryStats = computed(() => {
    const counts = { present: 0, half_day: 0, absent: 0 };
    Object.values(attendancesForm).forEach(v => counts[v]++);
    
    return [
        { label: 'Effectif Total', value: employees.value.length, icon: UsersIcon, bg: 'bg-slate-50', color: 'text-slate-600' },
        { label: 'Présents', value: counts.present, icon: CheckCircle2Icon, bg: 'bg-emerald-50', color: 'text-emerald-600' },
        { label: '1/2 Journées', value: counts.half_day, icon: ZapIcon, bg: 'bg-amber-50', color: 'text-amber-600' },
        { label: 'Absents', value: counts.absent, icon: XCircleIcon, bg: 'bg-rose-50', color: 'text-rose-600' },
    ];
});

const loadAttendances = async () => {
    isLoading.value = true;
    try {
        // Set default status for all employees
        employees.value.forEach(e => {
            if (!attendancesForm[e.id]) attendancesForm[e.id] = 'present';
            if (!overtimeForm[e.id]) overtimeForm[e.id] = 0;
        });

        // Load existing attendances from DB for this date
        const res = await axios.get(`/api/admin/attendances/${pointageDate.value}`);
        savedAttendances.value = res.data;
        res.data.forEach(a => {
            attendancesForm[a.employee_id] = a.status;
            overtimeForm[a.employee_id] = a.overtime_hours;
        });
    } catch (error) {
        console.error('Erreur lors du chargement:', error);
    } finally {
        isLoading.value = false;
    }
};

const markAllPresent = () => {
    employees.value.forEach(e => attendancesForm[e.id] = 'present');
};

const submitPointage = async () => {
    isSaving.value = true;
    try {
        const payload = Object.keys(attendancesForm).map(id => ({ 
            employee_id: id, 
            status: attendancesForm[id],
            overtime_hours: overtimeForm[id] || 0
        }));
        await axios.post('/api/admin/attendances', { 
            date: pointageDate.value, 
            attendances: payload 
        });
        // Reload from DB to show real wage_earned values
        await loadAttendances();
        toast.success('Pointage enregistré avec succès !');
    } catch (error) {
        toast.error('Erreur lors de l\'enregistrement');
    } finally {
        isSaving.value = false;
    }
};

const deleteAttendance = async (employeeId) => {
    if (!confirm('Supprimer le pointage de cet employé ?')) return;
    try {
        await axios.delete(`/api/admin/attendances/${employeeId}/${pointageDate.value}`);
        await loadAttendances();
        // Reset form for this employee
        attendancesForm[employeeId] = 'present';
        overtimeForm[employeeId] = 0;
    } catch (error) {
        toast.error('Erreur lors de la suppression.');
    }
};

const getEmployeeName = (employeeId) => {
    const emp = employees.value.find(e => e.id == employeeId);
    return emp ? emp.name : 'Inconnu';
};

const getStatusBadgeClass = (status) => {
    if (status === 'present') return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    if (status === 'half_day') return 'bg-amber-50 text-amber-600 border-amber-100';
    return 'bg-rose-50 text-rose-600 border-rose-100';
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(val || 0);
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

onMounted(async () => {
    // Load employees first
    try {
        const res = await axios.get('/api/admin/employees');
        employees.value = res.data;
        await loadAttendances();
    } catch (e) {
        console.error(e);
    }
});
</script>

<style scoped>
/* Smooth transitions for card state changes */
.grid > div {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
