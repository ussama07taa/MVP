<template>
  <div class="max-w-7xl mx-auto pb-10 px-4">
    <!-- Header with Employee Info -->
    <header class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
      <div class="animate-in fade-in slide-in-from-left duration-700">
        <div class="flex items-center mb-1">
          <button @click="goBack" class="mr-4 p-2 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
            <ChevronLeftIcon class="w-5 h-5 text-slate-600" />
          </button>
          <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mr-4">
            <UserIcon class="w-7 h-7 text-white"/>
          </div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Profil & <span class="text-indigo-600">Historique Paie</span></h1>
        </div>
        <p class="text-slate-400 font-medium ml-24 flex items-center">
           Analyse détaillée pour l'employé <span class="mx-1.5 font-black text-slate-700 uppercase">{{ employeeName || 'Chargement...' }}</span>
        </p>
      </div>

      <!-- Filters Row -->
      <div class="flex flex-wrap items-center gap-4 animate-in fade-in slide-in-from-right duration-700">
          <div class="flex items-center bg-white p-2 rounded-[22px] border border-slate-100 shadow-sm">
             <select v-model="selectedMonth" @change="loadHistory" class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer px-4 appearance-none">
                <option value="">Tous les mois</option>
                <option v-for="(m, i) in months" :key="i+1" :value="i+1">{{ m }}</option>
             </select>
             <div class="w-px h-4 bg-slate-100 mx-2"></div>
             <select v-model="selectedYear" @change="loadHistory" class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer px-4 appearance-none">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
             </select>
          </div>
          
          <button @click="loadHistory" class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md transition-all active:scale-95">
            <RotateCwIcon :class="isLoading ? 'animate-spin' : ''" class="w-5 h-5 text-indigo-600" />
          </button>
      </div>
    </header>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
        <!-- Total Paid Card -->
        <div class="bg-white rounded-[45px] p-8 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 relative overflow-hidden group hover:-translate-y-1 transition-all">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-1000"></div>
            <div class="relative z-10">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl w-fit mb-6">
                    <CircleDollarSignIcon class="w-6 h-6" />
                </div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Net Payé</h3>
                <div class="text-4xl font-black text-slate-900 tracking-tighter">
                    {{ formatCurrency(stats.total_net) }}<span class="text-lg font-bold text-slate-300 ml-1">DH</span>
                </div>
                <p class="text-[10px] font-bold text-emerald-500 mt-2 uppercase tracking-widest">{{ stats.count }} Versements</p>
            </div>
        </div>

        <!-- Total Advances Card -->
        <div class="bg-white rounded-[45px] p-8 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 relative overflow-hidden group hover:-translate-y-1 transition-all">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-rose-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-1000"></div>
            <div class="relative z-10">
                <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl w-fit mb-6">
                    <HandCoinsIcon class="w-6 h-6" />
                </div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Avances Déduites</h3>
                <div class="text-4xl font-black text-slate-900 tracking-tighter">
                    {{ formatCurrency(stats.total_advances) }}<span class="text-lg font-bold text-slate-300 ml-1">DH</span>
                </div>
            </div>
        </div>

        <!-- Total Days Card -->
        <div class="bg-white rounded-[45px] p-8 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 relative overflow-hidden group hover:-translate-y-1 transition-all">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-indigo-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-1000"></div>
            <div class="relative z-10">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl w-fit mb-6">
                    <CalendarCheckIcon class="w-6 h-6" />
                </div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Jours Travaillés</h3>
                <div class="text-4xl font-black text-slate-900 tracking-tighter">
                    {{ stats.total_days_worked }}<span class="text-lg font-bold text-slate-300 ml-1">Jours</span>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-[45px] shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 overflow-hidden">
        <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Période</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Jours</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Salaire Brut</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Avances</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Net Versé</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="entry in history" :key="entry.id" class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-10 py-8">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-4" :class="entry.is_advance_only ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'">
                                    <HandCoinsIcon v-if="entry.is_advance_only" class="w-4 h-4" />
                                    <ClockIcon v-else class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="font-black text-slate-700 leading-none mb-1">
                                        <span v-if="entry.is_advance_only">
                                            Salaf du {{ formatDate(entry.start_date) }}
                                        </span>
                                        <span v-else>
                                            Du {{ formatDate(entry.start_date) }}
                                        </span>
                                    </p>
                                    <p v-if="entry.is_advance_only" class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">
                                        En attente de clôture
                                    </p>
                                    <p v-else class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Au {{ formatDate(entry.end_date) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-8 text-center">
                            <span class="px-3 py-1 bg-slate-100 rounded-full text-xs font-black text-slate-600">{{ entry.days_worked }} j</span>
                        </td>
                        <td class="px-10 py-8 text-right font-bold text-slate-600">
                            {{ entry.gross_amount > 0 ? formatCurrency(entry.gross_amount) : '-' }}
                        </td>
                        <td class="px-10 py-8 text-right font-bold text-rose-500">
                            <span v-if="entry.advances_deducted > 0">
                                -{{ formatCurrency(entry.advances_deducted) }}
                            </span>
                            <span v-else class="text-slate-300">-</span>
                        </td>
                        <td class="px-10 py-8 text-right">
                            <span v-if="entry.is_advance_only" class="px-4 py-2 bg-amber-50 text-amber-600 rounded-xl font-black text-sm border border-amber-100">
                                Salaf
                            </span>
                            <span v-else class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl font-black text-sm">
                                {{ formatCurrency(entry.net_paid) }} DH
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="history.length === 0 && !isLoading" class="py-20 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <HistoryIcon class="w-10 h-10 text-slate-200" />
            </div>
            <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Aucun historique trouvé</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
  ChevronLeftIcon, UserIcon, RotateCwIcon, CircleDollarSignIcon, 
  HandCoinsIcon, CalendarCheckIcon, ClockIcon, HistoryIcon 
} from 'lucide-vue-next';

const { props } = usePage();
const employeeId = props.id; // Inertia passes route params as props to the component
const employeeName = ref('');

const history = ref([]);
const stats = ref({
    total_net: 0,
    total_advances: 0,
    total_days_worked: 0,
    count: 0
});

const isLoading = ref(true);
const selectedMonth = ref('');
const selectedYear = ref(new Date().getFullYear());

const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
const years = [2024, 2025, 2026];

const loadHistory = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/admin/employees/${employeeId}/history`, {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            }
        });
        history.value = response.data.history;
        stats.value = response.data.stats;
        
        // Get employee name from stats if not set yet
        if (!employeeName.value && response.data.stats.employee_name) {
            employeeName.value = response.data.stats.employee_name;
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'historique:', error);
    } finally {
        isLoading.value = false;
    }
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(val);
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
};

const goBack = () => {
    window.history.back();
};

onMounted(() => {
    loadHistory();
});
</script>
