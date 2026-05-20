<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 font-sans">
    
    <!-- Header -->
    <div class="mb-8 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
      <div class="flex items-center">
        <div class="mr-6 bg-white p-4 rounded-3xl shadow-sm border border-slate-100">
           <ActivityIcon class="w-10 h-10 text-indigo-600" />
        </div>
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-2">Audit & Activités</h1>
          <p class="text-slate-500 font-medium text-sm">Traçabilité complète des actions effectuées sur le système.</p>
        </div>
      </div>

      <div class="flex flex-col md:flex-row items-center gap-4">
        <!-- Actualiser Button -->
        <button @click="loadLogs" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-4 bg-white border border-slate-100 rounded-[20px] shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-indigo-600 transition-transform duration-500" />
        </button>

        <!-- Filters -->
        <div class="bg-white p-1.5 rounded-[22px] shadow-sm border border-slate-100 flex items-center">
          <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-600'" class="px-5 py-2.5 rounded-2xl transition-all duration-300 text-sm font-black uppercase tracking-widest">Tout</button>
          <button @click="setFilter('created')" :class="filter === 'created' ? 'bg-emerald-500 text-white shadow-md' : 'text-emerald-400 hover:text-emerald-600'" class="px-5 py-2.5 rounded-2xl transition-all duration-300 text-sm font-black uppercase tracking-widest mx-1">Créations</button>
          <button @click="setFilter('updated')" :class="filter === 'updated' ? 'bg-amber-500 text-white shadow-md' : 'text-amber-400 hover:text-amber-600'" class="px-5 py-2.5 rounded-2xl transition-all duration-300 text-sm font-black uppercase tracking-widest mr-1">Modifs</button>
          <button @click="setFilter('deleted')" :class="filter === 'deleted' ? 'bg-rose-500 text-white shadow-md' : 'text-rose-400 hover:text-rose-600'" class="px-5 py-2.5 rounded-2xl transition-all duration-300 text-sm font-black uppercase tracking-widest">Suppressions</button>
        </div>
      </div>
    </div>

    <!-- Logs List -->
    <div v-if="logs.length > 0" class="bg-white rounded-[40px] shadow-soft border border-slate-100 overflow-hidden relative">
      <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date / Temps</th>
              <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</th>
              <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
              <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Module (Entité)</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Détails</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/50 transition-colors group">
              <!-- Date -->
              <td class="px-8 py-5">
                <div class="font-black text-slate-700 text-sm">{{ log.date }}</div>
                <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5 flex items-center">
                   <ClockIcon class="w-3 h-3 mr-1" /> {{ log.time_ago }}
                </div>
              </td>
              
              <!-- User -->
              <td class="px-6 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-xs uppercase shadow-sm">
                     {{ log.user.substring(0, 2) }}
                  </div>
                  <span class="font-bold text-slate-800 text-sm">{{ log.user }}</span>
                </div>
              </td>
              
              <!-- Action -->
              <td class="px-6 py-5">
                <span class="inline-flex items-center px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest" :class="getActionBadgeClass(log.action)">
                   {{ log.action }}
                </span>
              </td>
              
              <!-- Subject -->
              <td class="px-6 py-5">
                <span class="font-black text-slate-600 uppercase text-xs tracking-wider bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                  {{ log.subject_type }}
                </span>
              </td>
              
              <!-- Details -->
              <td class="px-8 py-5 text-right">
                <div v-if="log.is_update" class="flex justify-end">
                   <button @click="openDiffModal(log)" class="flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-xl transition-all duration-300 text-xs font-black shadow-sm group/btn border border-indigo-100 hover:border-indigo-600">
                      <EyeIcon class="w-4 h-4" /> Voir les changements
                   </button>
                </div>
                <div v-else class="text-slate-300 text-xs font-bold uppercase tracking-widest">
                   -
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination Controls (Simple) -->
      <div v-if="meta.last_page > 1" class="p-6 border-t border-slate-100 flex justify-between items-center bg-slate-50/30">
         <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Page {{ currentPage }} sur {{ meta.last_page }}</span>
         <div class="flex gap-2">
            <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-600 disabled:opacity-50 hover:bg-slate-50 transition-colors shadow-sm">
               <ChevronLeftIcon class="w-5 h-5" />
            </button>
            <button @click="changePage(currentPage + 1)" :disabled="currentPage === meta.last_page" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-600 disabled:opacity-50 hover:bg-slate-50 transition-colors shadow-sm">
               <ChevronRightIcon class="w-5 h-5" />
            </button>
         </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!isLoading" class="py-32 bg-white rounded-[60px] border-2 border-dashed border-slate-100 flex flex-col items-center justify-center text-slate-300">
       <ActivityIcon class="w-20 h-20 mb-4 opacity-20" />
       <p class="font-black uppercase tracking-widest">Aucune activité trouvée</p>
    </div>

    <!-- Diff Modal -->
    <div v-if="selectedLog && selectedLog.is_update" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md animate-fade-in" @click="selectedLog = null">
       <div class="bg-white rounded-[40px] w-full max-w-3xl shadow-2xl overflow-hidden transform transition-all" @click.stop>
          
          <div class="bg-indigo-600 p-8 flex justify-between items-start relative overflow-hidden">
             <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
             <div>
                <h3 class="text-2xl font-black text-white flex items-center">
                   <FileSearchIcon class="w-6 h-6 mr-3 text-indigo-200" /> Changements pour {{ selectedLog.subject_type }} #{{ selectedLog.subject_id }}
                </h3>
                <p class="text-sm text-indigo-100 mt-1 font-medium">Analyse comparative détaillée</p>
             </div>
             <button @click="selectedLog = null" class="text-indigo-200 hover:text-white transition-colors bg-white/10 p-2 rounded-xl backdrop-blur-sm">
                <XIcon class="w-6 h-6" />
             </button>
          </div>
          
          <div class="p-8 max-h-[60vh] overflow-y-auto">
             <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 font-black text-slate-400 text-[10px] uppercase tracking-widest mb-4 border-b border-slate-100 pb-3">
                <div class="pl-4">Champ Modifié</div>
                <div>Ancienne Valeur</div>
                <div>Nouvelle Valeur</div>
             </div>
             
             <div v-for="(newValue, key) in selectedLog.properties.new" :key="key">
                <div v-if="selectedLog.properties.old && selectedLog.properties.old[key] !== newValue" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 items-center py-4 border-b border-slate-50 text-sm hover:bg-slate-50 transition-colors px-4 rounded-xl">
                   
                   <div class="font-black text-slate-800">{{ formatKey(key) }}</div>
                   
                   <div class="text-rose-500 font-bold line-through decoration-rose-300">
                      {{ formatValue(selectedLog.properties.old[key], key) }}
                   </div>
                   
                   <div class="text-emerald-600 font-black flex items-center">
                      <PlusIcon class="w-3 h-3 mr-1.5" /> {{ formatValue(newValue, key) }}
                   </div>
                   
                </div>
             </div>
          </div>
          
          <div class="bg-slate-50 p-6 border-t border-slate-100 flex justify-end">
             <button @click="selectedLog = null" class="px-8 py-4 bg-slate-900 text-white font-black rounded-2xl hover:bg-slate-800 transition-all shadow-xl uppercase text-xs tracking-widest active:scale-95">
                Fermer
             </button>
          </div>
       </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
  ActivityIcon, RotateCwIcon, ClockIcon, EyeIcon, FileSearchIcon, XIcon,
  MinusIcon, PlusIcon, ChevronLeftIcon, ChevronRightIcon
} from 'lucide-vue-next';

// State
const logs = ref([]);
const meta = ref({ total: 0, last_page: 1 });
const isLoading = ref(false);
const filter = ref('all');
const currentPage = ref(1);

// Modal state
const selectedLog = ref(null);

// Methods
const loadLogs = async (page = 1) => {
    isLoading.value = true;
    try {
        const res = await axios.get(`/api/admin/activity-logs?page=${page}&event=${filter.value}`);
        logs.value = res.data.data;
        meta.value = res.data.meta;
        currentPage.value = page;
    } catch(e) {
        console.error("Failed to load logs", e);
    } finally {
        isLoading.value = false;
    }
};

const setFilter = (f) => {
    filter.value = f;
    loadLogs(1); // Reset to first page when filtering
};

const changePage = (page) => {
    if (page >= 1 && page <= meta.value.last_page) {
        loadLogs(page);
    }
};

const getActionBadgeClass = (action) => {
    if (!action) return 'bg-slate-100 text-slate-600 border border-slate-200';
    const act = action.toLowerCase();
    if (act.includes('created') || act.includes('créé')) return 'bg-emerald-50 text-emerald-600 border border-emerald-100';
    if (act.includes('updated') || act.includes('modifié')) return 'bg-amber-50 text-amber-600 border border-amber-100';
    if (act.includes('deleted') || act.includes('supprimé')) return 'bg-rose-50 text-rose-600 border border-rose-100';
    return 'bg-slate-100 text-slate-600 border border-slate-200';
};

const openDiffModal = (log) => {
    selectedLog.value = log;
};

const formatKey = (key) => {
    const translations = {
        'total_sell_price': 'Total Vente',
        'total_cost_price': 'Total Achat',
        'amount_paid': 'Montant Payé',
        'status': 'Statut',
        'quantity': 'Quantité',
        'base_price_sell': 'Prix Vente',
        'cost_price': 'Prix Achat',
        'color_name': 'Nom Couleur',
        'color_code': 'Code Couleur',
        'name': 'Nom',
        'phone': 'Téléphone',
        'total_credit': 'Crédit Total',
        'updated_at': 'Date de Modification',
        'created_at': 'Date de Création',
        'deleted_at': 'Date de Suppression',
        'total_refunded': 'Total Remboursé',
        'reason': 'Raison / Motif',
        'notes': 'Notes / Remarques',
        'user_id': 'Utilisateur ID',
        'client_id': 'Client ID',
        'total_length_remaining': 'Longueur Restante',
        'alert_threshold': 'Seuil d\'Alerte'
    };
    return translations[key] || key.replace(/_/g, ' ').toUpperCase();
};

const formatValue = (val, key = '') => {
    if (val === null || val === undefined) return 'N/A';
    if (val === '') return '(Vide)';
    if (typeof val === 'boolean') return val ? 'Oui' : 'Non';
    
    // Handle Date/Time strings
    if (typeof val === 'string' && (val.includes('T') && val.includes('Z') || val.match(/^\d{4}-\d{2}-\d{2}/))) {
        try {
            const date = new Date(val);
            if (!isNaN(date.getTime())) {
                return new Intl.DateTimeFormat('fr-FR', {
                    day: '2-digit', month: 'long', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                }).format(date);
            }
        } catch(e) {}
    }

    // Handle Monetary values
    if (['price', 'amount', 'total', 'cost', 'revenue', 'refunded'].some(k => key.toLowerCase().includes(k))) {
        const num = parseFloat(val);
        if (!isNaN(num)) return num.toFixed(2) + ' DH';
    }

    if (typeof val === 'object') return JSON.stringify(val);
    return val;
};

// Init
onMounted(() => {
    loadLogs();
});
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.shadow-soft {
    box-shadow: 0 4px 40px rgba(0,0,0,0.03);
}
</style>
