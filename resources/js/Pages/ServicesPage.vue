<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 font-sans">
    
    <!-- Header & Search -->
    <div class="mb-8 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
      <div class="flex items-center">
        <div class="mr-6 bg-white p-4 rounded-3xl shadow-sm border border-slate-100">
           <SettingsIcon class="w-10 h-10 text-brand-600" />
        </div>
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-2">Services & Tarifs</h1>
          <p class="text-slate-500 font-medium text-sm">Gérez les prix des services de découpe, pose et autres prestations.</p>
        </div>
      </div>

      <div class="flex flex-col md:flex-row items-center gap-4 flex-1 max-w-4xl">
        <!-- Actualiser Button (Master UX) -->
        <button @click="loadServices" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-4 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-brand-600 transition-transform duration-500" />
        </button>

        <div class="relative flex-1 w-full">
           <SearchIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
           <input v-model="searchQuery" type="text" placeholder="Rechercher un service..." 
                  class="w-full pl-12 pr-4 py-4 bg-white border-slate-100 rounded-[20px] shadow-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-bold text-slate-700 transition-all">
        </div>
        <button @click="toggleAddForm" class="w-full md:w-auto bg-brand-600 text-white px-8 py-4 rounded-[20px] font-black shadow-lg shadow-brand-100 hover:bg-brand-700 transition-all flex items-center justify-center whitespace-nowrap">
          <PlusCircleIcon class="w-5 h-5 mr-2" /> {{ showAddForm ? (editingId ? 'Annuler Modif.' : 'Fermer') : 'Ajouter Service' }}
        </button>
      </div>
    </div>

    <!-- Add/Edit Form -->
    <transition name="fade-slide">
      <div v-if="showAddForm" class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-soft mb-8 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-brand-500"></div>
        <h3 class="text-xl font-black mb-8 text-slate-800 flex items-center">
          <Edit3Icon v-if="editingId" class="w-5 h-5 mr-3 text-brand-500" />
          {{ editingId ? 'Modifier le Service' : 'Nouveau Service' }}
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nom du Service</label>
            <input v-model="form.name" type="text" placeholder="ex: Découpe MDF" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type de Calcul</label>
            <select v-model="form.calculation_type" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
               <option value="per_sheet">Par Plaque (Plaque Entière)</option>
               <option value="per_meter">Par Mètre (Linéaire)</option>
               <option value="fixed">Prix Fixe (Forfait)</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-brand-600 uppercase tracking-widest">Prix Unitaire (DH)</label>
            <input v-model="form.unit_price" type="number" step="0.5" class="w-full bg-brand-50 border-brand-100 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black text-brand-700 shadow-inner">
          </div>
        </div>

        <div class="mt-8 flex justify-end gap-4 border-t border-slate-50 pt-6">
          <button @click="saveService" class="bg-slate-900 text-white px-12 py-4 rounded-2xl font-black shadow-xl hover:bg-brand-600 transition-all uppercase tracking-widest text-xs flex items-center">
            <CheckCircleIcon class="w-5 h-5 mr-3" /> {{ editingId ? 'Mettre à jour' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </transition>

    <!-- Services Grid -->
    <div v-if="filteredServices.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div v-for="srv in filteredServices" :key="srv.id" class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-soft group hover:border-brand-200 hover:shadow-xl transition-all relative overflow-hidden flex flex-col">
        
        <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-brand-50 opacity-0 group-hover:opacity-100 transition-all duration-700"></div>

        <div class="flex justify-between items-start mb-6 relative z-10">
          <div class="w-16 h-16 bg-brand-50 border-4 border-white shadow-sm rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
            <ScissorsIcon v-if="srv.name.toLowerCase().includes('coupe') || srv.name.toLowerCase().includes('tefsil')" class="w-8 h-8 text-brand-600" />
            <HammerIcon v-else class="w-8 h-8 text-brand-600" />
          </div>
          <div class="flex gap-2">
             <button @click="editService(srv)" class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-all"><Edit3Icon class="w-4 h-4" /></button>
             <button @click="confirmDeleteService(srv.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all"><Trash2Icon class="w-4 h-4" /></button>
          </div>
        </div>
        
        <h3 class="text-2xl font-black text-slate-900 mb-2 relative z-10">{{ srv.name }}</h3>
        <p class="text-[10px] font-black text-slate-400 mb-8 uppercase tracking-[0.2em] relative z-10">Type de calcul: <span class="text-brand-600">{{ getCalculationLabel(srv.calculation_type) }}</span></p>
        
        <div class="bg-slate-50 border border-slate-100 rounded-[24px] p-6 flex justify-between items-end mt-auto relative z-10">
          <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Prix Actuel</span>
          <div class="text-4xl font-black text-slate-900 tracking-tighter leading-none">{{ srv.unit_price }} <span class="text-sm font-bold text-slate-400">DH</span></div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="py-32 bg-white rounded-[60px] border-2 border-dashed border-slate-100 flex flex-col items-center justify-center text-slate-300">
      <PackageSearchIcon class="w-24 h-24 mb-6 opacity-20" />
      <p class="text-2xl font-black uppercase tracking-widest mb-2">Aucun Service Trouvé</p>
    </div>

    <!-- Delete Confirmation Modal -->
    <TransitionRoot appear :show="deleteModalOpen" as="template">
      <Dialog as="div" @close="deleteModalOpen = false" class="relative z-50">
        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-[32px] bg-white p-8 text-left align-middle shadow-2xl transition-all border border-slate-100">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-50 mb-6 mx-auto">
                  <AlertTriangleIcon class="w-8 h-8 text-red-500" />
                </div>
                <DialogTitle as="h3" class="text-2xl font-black text-center text-slate-900 mb-2">Supprimer ce service ?</DialogTitle>
                <div class="mt-2 text-center">
                  <p class="text-sm font-medium text-slate-500">Cette action est irréversible. Les anciennes factures garderont le prix au moment de l'achat.</p>
                </div>

                <div class="mt-8 flex gap-4">
                  <button type="button" class="w-full justify-center rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none transition-all" @click="deleteModalOpen = false">
                    Annuler
                  </button>
                  <button type="button" class="w-full justify-center rounded-2xl border border-transparent bg-red-600 px-4 py-4 text-sm font-black text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-100 transition-all shadow-lg shadow-red-200" @click="executeDelete">
                    Oui, supprimer
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { SettingsIcon, ScissorsIcon, HammerIcon, PlusCircleIcon, Edit3Icon, Trash2Icon, SearchIcon, CheckCircleIcon, AlertTriangleIcon, PackageSearchIcon, RotateCwIcon } from 'lucide-vue-next';

const services = ref([]);
const showAddForm = ref(false);
const editingId = ref(null);
const searchQuery = ref('');
const isLoading = ref(false);

// Modal State
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

const form = ref({ name: '', calculation_type: 'per_sheet', unit_price: 0 });

const filteredServices = computed(() => {
  if (!searchQuery.value) return services.value;
  const q = searchQuery.value.toLowerCase();
  return services.value.filter(s => s.name.toLowerCase().includes(q));
});

const loadServices = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/services');
    services.value = res.data;
  } catch(e) { 
    console.error(e); 
  } finally {
    isLoading.value = false;
  }
};

const toggleAddForm = () => {
  if (showAddForm.value && editingId.value) { editingId.value = null; resetForm(); }
  showAddForm.value = !showAddForm.value;
};

const resetForm = () => { form.value = { name: '', calculation_type: 'per_sheet', unit_price: 0 }; };

const saveService = async () => {
  try {
    if (editingId.value) await axios.put(`/api/admin/services/${editingId.value}`, form.value);
    else await axios.post('/api/admin/services', form.value);
    
    showAddForm.value = false; resetForm(); editingId.value = null; loadServices();
  } catch(e) { toast.error('Erreur'); }
};

const editService = (srv) => {
  editingId.value = srv.id;
  form.value = { name: srv.name, calculation_type: srv.calculation_type, unit_price: srv.unit_price };
  showAddForm.value = true;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const confirmDeleteService = (id) => {
  itemToDelete.value = id;
  deleteModalOpen.value = true;
};

const executeDelete = async () => {
  if (!itemToDelete.value) return;
  try { 
    await axios.delete(`/api/admin/services/${itemToDelete.value}`); 
    loadServices(); 
    deleteModalOpen.value = false;
    itemToDelete.value = null;
  } catch(e) { toast.error('Erreur'); }
};

const getCalculationLabel = (type) => {
  const map = { 'per_sheet': 'Par Plaque', 'per_meter': 'Par Mètre', 'fixed': 'Prix Fixe' };
  return map[type] || type;
};

onMounted(() => loadServices());
</script>

<style scoped>
.shadow-soft { box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-20px); }
</style>
