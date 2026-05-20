<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 font-sans">
    
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center">
        <div class="mr-6 bg-white p-4 rounded-3xl shadow-sm border border-slate-100">
           <ShieldIcon class="w-10 h-10 text-indigo-600" />
        </div>
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-2">Utilisateurs & Accès</h1>
          <p class="text-slate-500 font-medium text-sm">Gérez les comptes qui peuvent se connecter au système.</p>
        </div>
      </div>
      
      <button @click="openCreateModal" class="flex items-center justify-center gap-2 px-6 py-4 bg-indigo-600 text-white rounded-2xl hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-600/30 transition-all active:scale-95 font-black text-sm uppercase tracking-widest">
        <PlusIcon class="w-5 h-5" /> Nouveau Utilisateur
      </button>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-[40px] shadow-soft border border-slate-100 overflow-hidden">
      <div v-if="isLoading" class="p-12 flex justify-center">
        <LoaderIcon class="w-10 h-10 text-indigo-600 animate-spin" />
      </div>
      <div v-else-if="users.length === 0" class="py-32 flex flex-col items-center justify-center text-slate-300">
        <UsersIcon class="w-20 h-20 mb-4 opacity-20" />
        <p class="font-black uppercase tracking-widest">Aucun utilisateur trouvé</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</th>
              <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Email / Identifiant</th>
              <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Rôle</th>
              <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors group">
              <!-- Name -->
              <td class="px-8 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-black text-sm shadow-sm" :class="getAvatarClass(user.role)">
                     {{ user.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <span class="font-black text-slate-800">{{ user.name }}</span>
                </div>
              </td>
              
              <!-- Email -->
              <td class="px-6 py-5">
                <span class="text-slate-500 font-bold text-sm">{{ user.email }}</span>
              </td>
              
              <!-- Role -->
              <td class="px-6 py-5">
                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border" :class="getRoleBadgeClass(user.role)">
                   {{ user.role === 'admin' ? 'Administrateur' : (user.role === 'cashier' ? 'Caissier' : 'Ouvrier (Atelier)') }}
                </span>
              </td>
              
              <!-- Status -->
              <td class="px-6 py-5">
                <span class="flex items-center gap-2 text-xs font-black uppercase tracking-widest" :class="user.is_active ? 'text-emerald-500' : 'text-rose-500'">
                  <span class="w-2 h-2 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                  {{ user.is_active ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              
              <!-- Actions -->
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openEditModal(user)" class="p-2 bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 rounded-xl transition-colors" title="Modifier">
                    <PenIcon class="w-5 h-5" />
                  </button>
                  <button @click="toggleStatus(user)" class="p-2 bg-slate-50 text-slate-600 hover:bg-rose-50 hover:text-rose-600 rounded-xl transition-colors" :title="user.is_active ? 'Désactiver' : 'Activer'" :disabled="isCurrentUser(user.id)">
                    <BanIcon v-if="user.is_active" class="w-5 h-5" :class="{ 'opacity-30': isCurrentUser(user.id) }" />
                    <CheckCircleIcon v-else class="w-5 h-5 text-emerald-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click="closeModal">
      <div class="bg-white rounded-[30px] w-full max-w-lg mx-4 sm:mx-auto shadow-2xl overflow-hidden" @click.stop>
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h3 class="text-xl font-black text-slate-800">{{ form.id ? 'Modifier l\'utilisateur' : 'Nouveau Utilisateur' }}</h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 bg-white p-2 rounded-xl shadow-sm border border-slate-100">
            <XIcon class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveUser" class="p-8 space-y-6">
          <!-- Name -->
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nom Complet</label>
            <input v-model="form.name" type="text" required
              class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600 transition-all placeholder:text-slate-300 placeholder:font-medium"
              placeholder="Ex: Brahim Taaouati" />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Adresse Email</label>
            <input v-model="form.email" type="email" required
              class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600 transition-all placeholder:text-slate-300 placeholder:font-medium"
              placeholder="Ex: brahim@erp.com" />
          </div>

          <!-- Password -->
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
              Mot de passe <span v-if="form.id" class="text-indigo-400 text-[9px] lowercase">(Laisser vide pour ne pas changer)</span>
            </label>
            <input v-model="form.password" type="password" :required="!form.id"
              class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600 transition-all placeholder:text-slate-300 placeholder:font-medium"
              placeholder="********" minlength="6" />
          </div>

          <!-- Role -->
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Rôle</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <label class="relative cursor-pointer">
                <input type="radio" v-model="form.role" value="admin" class="peer sr-only" />
                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-rose-500 peer-checked:bg-rose-50 transition-all text-center h-full">
                  <div class="font-black text-slate-700 peer-checked:text-rose-600">Admin</div>
                  <div class="text-[10px] text-slate-400 font-bold mt-1">Accès total</div>
                </div>
              </label>
              
              <label class="relative cursor-pointer">
                <input type="radio" v-model="form.role" value="cashier" class="peer sr-only" />
                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all text-center h-full">
                  <div class="font-black text-slate-700 peer-checked:text-emerald-600">Caissier</div>
                  <div class="text-[10px] text-slate-400 font-bold mt-1">Point de vente</div>
                </div>
              </label>

              <label class="relative cursor-pointer">
                <input type="radio" v-model="form.role" value="worker" class="peer sr-only" />
                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition-all text-center h-full">
                  <div class="font-black text-slate-700 peer-checked:text-indigo-600">Ouvrier</div>
                  <div class="text-[10px] text-slate-400 font-bold mt-1">Atelier Mobile</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Submit -->
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="px-6 py-3 bg-slate-100 text-slate-600 font-black rounded-xl hover:bg-slate-200 transition-colors uppercase text-xs tracking-widest">
              Annuler
            </button>
            <button type="submit" :disabled="isSaving" class="flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white font-black rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/30 uppercase text-xs tracking-widest disabled:opacity-50">
              <LoaderIcon v-if="isSaving" class="w-4 h-4 animate-spin" />
              {{ form.id ? 'Mettre à jour' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
  ShieldIcon, PlusIcon, PenIcon, BanIcon, CheckCircleIcon, XIcon, LoaderIcon, UsersIcon
} from 'lucide-vue-next';

const users = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const showModal = ref(false);

const authUserId = window.authUser ? window.authUser.id : null;

const form = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  role: 'cashier',
  is_active: true
});

const loadUsers = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/users');
    users.value = res.data;
  } catch (error) {
    console.error("Erreur lors du chargement des utilisateurs", error);
    alert('Erreur de chargement.');
  } finally {
    isLoading.value = false;
  }
};

const openCreateModal = () => {
  form.value = { id: null, name: '', email: '', password: '', role: 'cashier', is_active: true };
  showModal.value = true;
};

const openEditModal = (user) => {
  form.value = { 
    id: user.id, 
    name: user.name, 
    email: user.email, 
    password: '', // Blank by default, only sent if modified
    role: user.role,
    is_active: user.is_active 
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveUser = async () => {
  isSaving.value = true;
  try {
    if (form.value.id) {
      await axios.put(`/api/admin/users/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/admin/users', form.value);
    }
    await loadUsers();
    closeModal();
  } catch (error) {
    console.error("Erreur d'enregistrement", error);
    if(error.response && error.response.data && error.response.data.message) {
        alert(error.response.data.message);
    } else {
        alert('Erreur lors de l\'enregistrement.');
    }
  } finally {
    isSaving.value = false;
  }
};

const toggleStatus = async (user) => {
  if (isCurrentUser(user.id)) return;
  
  if (!confirm(`Voulez-vous vraiment ${user.is_active ? 'désactiver' : 'activer'} l'utilisateur ${user.name} ?`)) return;
  
  try {
    await axios.put(`/api/admin/users/${user.id}`, {
      name: user.name,
      email: user.email,
      role: user.role,
      is_active: !user.is_active
    });
    user.is_active = !user.is_active;
  } catch (error) {
    console.error("Erreur lors de la modification du statut", error);
    alert('Erreur réseau.');
  }
};

const isCurrentUser = (id) => {
    return id === authUserId;
};

const getRoleBadgeClass = (role) => {
  if (role === 'admin') return 'bg-rose-50 text-rose-600 border-rose-100';
  if (role === 'worker') return 'bg-indigo-50 text-indigo-600 border-indigo-100';
  return 'bg-emerald-50 text-emerald-600 border-emerald-100';
};

const getAvatarClass = (role) => {
  if (role === 'admin') return 'bg-rose-500';
  if (role === 'worker') return 'bg-indigo-500';
  return 'bg-emerald-500';
};

onMounted(() => {
  loadUsers();
});
</script>

<style scoped>
.shadow-soft {
  box-shadow: 0 4px 40px rgba(0,0,0,0.03);
}
</style>
