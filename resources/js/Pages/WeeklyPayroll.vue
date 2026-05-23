<template>
  <div class="max-w-7xl mx-auto pb-10 px-4">
    <!-- Header with Premium Design -->
    <header class="mb-10 flex flex-col sm:flex-row justify-between items-start md:items-center gap-6">
      <div class="animate-in fade-in slide-in-from-left duration-700">
        <div class="flex items-center mb-1">
          <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mr-4">
            <CalculatorIcon class="w-7 h-7 text-white"/>
          </div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Gestion de la <span class="text-indigo-600">Paie Hebdo</span></h1>
        </div>
        <p class="text-slate-400 font-medium ml-14 flex items-center">
           <CalendarIcon class="w-4 h-4 mr-2" />
           Période du <span class="mx-1.5 font-black text-slate-700">{{ startDate }}</span> au <span class="mx-1.5 font-black text-slate-700">{{ endDate }}</span>
        </p>
      </div>

      <div class="flex items-center gap-4 animate-in fade-in slide-in-from-right duration-700">
          <button @click="loadPayroll" 
            :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
            class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md transition-all active:scale-95"
            title="Actualiser">
            <RotateCwIcon :class="isLoading ? 'animate-spin' : ''" class="w-5 h-5 text-indigo-600" />
          </button>
          
          <button @click="closePayroll" 
            :disabled="isLoading || payrollData.details.length === 0"
            class="px-6 py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all active:scale-95 flex items-center disabled:opacity-50 disabled:pointer-events-none">
            <CheckCircleIcon class="w-5 h-5 mr-2" /> Clôturer la Paie
          </button>
      </div>
    </header>

    <!-- Top Summary Card (Masterpiece Card) -->
    <div class="mb-10 animate-in fade-in zoom-in-95 duration-1000">
        <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[45px] p-4 md:p-10 shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-emerald-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col sm:flex-row justify-between items-center gap-10">
                <div class="text-center md:text-left">
                    <h3 class="text-xs font-black text-indigo-400 uppercase tracking-[0.3em] mb-4">Total Net à Payer ce Samedi</h3>
                    <div class="text-6xl md:text-7xl font-black text-white tracking-tighter">
                        {{ formatCurrency(payrollData.grand_total) }}<span class="text-2xl text-slate-500 ml-2">DH</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full md:w-auto">
                    <div class="bg-white/5 backdrop-blur-md p-6 rounded-3xl border border-white/5">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Employés</p>
                        <p class="text-2xl font-black text-white">{{ payrollData.details.length }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md p-6 rounded-3xl border border-white/5">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Avances</p>
                        <p class="text-2xl font-black text-rose-400">{{ formatCurrency(totalAdvances) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Table -->
    <div class="bg-white rounded-[45px] shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 overflow-hidden animate-in fade-in slide-in-from-bottom duration-1000">
        <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Employé</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Jours Travaillés</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Base / Heures Supp</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Total Brut</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Avances (Salaf)</th>
                        <th class="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Net à Payer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="item in payrollData.details" :key="item.employee_id" class="hover:bg-slate-50/80 transition-colors group">
                        <td class="px-10 py-8">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xs mr-4 group-hover:scale-110 transition-transform">
                                    {{ item.employee_name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 leading-none mb-1">{{ item.employee_name }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">{{ item.daily_salary }} DH / jour</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-8 text-center">
                            <div class="inline-flex items-center px-4 py-2 bg-slate-100 rounded-full">
                                <span class="text-sm font-black text-slate-700">{{ item.days_worked }}</span>
                                <span class="text-[10px] font-bold text-slate-400 ml-1">Jours</span>
                            </div>
                            <div class="mt-2 text-[9px] text-slate-300 font-bold uppercase">
                                {{ item.present_days }}P + {{ item.half_days }}½
                            </div>
                        </td>
                        <td class="px-10 py-8 text-right">
                            <div class="font-bold text-slate-500 text-xs mb-1">Base: {{ formatCurrency(item.base_wages) }} DH</div>
                            <div class="font-bold text-amber-500 text-xs">Supp: +{{ formatCurrency(item.overtime_wages) }} DH</div>
                        </td>
                        <td class="px-10 py-8 text-right font-black text-slate-800 text-lg">
                            {{ formatCurrency(item.gross_pay) }} <span class="text-[10px] text-slate-400 ml-1">DH</span>
                        </td>
                        <td class="px-10 py-8 text-right font-black text-rose-500">
                            -{{ formatCurrency(item.advances) }} DH
                        </td>
                        <td class="px-10 py-8 text-right">
                            <span class="text-xl font-black text-indigo-600">{{ formatCurrency(item.net_pay) }}</span>
                            <span class="text-[10px] font-bold text-slate-400 ml-1">DH</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div v-if="payrollData.details.length === 0 && !isLoading" class="py-20 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <UsersIcon class="w-10 h-10 text-slate-200" />
            </div>
            <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Aucune donnée de paie pour cette période</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  CalculatorIcon, CalendarIcon, RotateCwIcon, 
  CheckCircleIcon, UsersIcon 
} from 'lucide-vue-next';

const isLoading = ref(true);
const payrollData = ref({
    details: [],
    grand_total: 0
});

// Calculate start and end dates (This week: Monday to Saturday)
const today = new Date();
const firstDay = new Date(today.setDate(today.getDate() - today.getDay() + 1)); // Monday
const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 6)); // Saturday

const startDate = ref(firstDay.toISOString().split('T')[0]);
const endDate = ref(lastDay.toISOString().split('T')[0]);

const totalAdvances = computed(() => {
    return payrollData.value.details.reduce((sum, item) => sum + item.advances, 0);
});

const loadPayroll = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/admin/payroll/weekly', {
            params: {
                start_date: startDate.value,
                end_date: endDate.value
            }
        });
        payrollData.value = response.data;
    } catch (error) {
        console.error('Erreur lors du chargement de la paie:', error);
    } finally {
        isLoading.value = false;
    }
};

const closePayroll = async () => {
    if (!confirm('Êtes-vous sûr de vouloir clôturer la paie ? Cela enregistrera l\'historique et marquera les avances comme déduites.')) return;
    
    isLoading.value = true;
    try {
        await axios.post('/api/admin/payroll/close', { 
            start_date: startDate.value,
            end_date: endDate.value
        });
        toast.success('Paie clôturée avec succès !');
        loadPayroll(); // Refresh
    } catch (error) {
        toast.error('Erreur lors de la clôture de la paie');
    } finally {
        isLoading.value = false;
    }
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(val);
};

onMounted(() => {
    loadPayroll();
});
</script>

<style scoped>
@keyframes slide-in-bottom {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.animate-slide-in-bottom {
  animation: slide-in-bottom 0.7s ease-out forwards;
}
</style>
