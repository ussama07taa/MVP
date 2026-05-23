<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 md:p-10 font-sans selection:bg-brand-500 selection:text-white">
    
    <!-- Premium Header -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12 relative">
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 bg-brand-600 rounded-2xl flex items-center justify-center shadow-lg shadow-brand-500/20">
            <ActivityIcon class="w-6 h-6 text-white animate-pulse" />
          </div>
          <h1 class="text-5xl font-black text-slate-900 tracking-tight">Atelier <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-indigo-600">Pro</span></h1>
        </div>
        <p class="text-sm font-bold text-slate-400 ml-1 flex items-center gap-2">
          Suivi opérationnel et file d'attente en temps réel
        </p>
      </div>
      
      <button @click="showAddModal = true" class="group relative z-10 px-10 py-5 bg-slate-900 rounded-[2.5rem] overflow-hidden transition-all hover:scale-105 active:scale-95 shadow-2xl shadow-slate-900/20">
        <div class="absolute inset-0 bg-gradient-to-r from-brand-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <div class="relative flex items-center gap-4 text-white uppercase tracking-[0.2em] font-black text-xs">
          <PlusIcon class="w-6 h-6 transition-transform group-hover:rotate-90 duration-500" />
          Nouveau Client
        </div>
      </button>
      
      <!-- Decorative Background Blur -->
      <div class="absolute -top-20 -left-20 w-64 h-64 bg-brand-200/30 blur-[100px] rounded-full"></div>
    </header>

    <!-- Stats Cards (Glassmorphism) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
      <div v-for="stat in stats" :key="stat.label" 
        class="relative group overflow-hidden bg-white/60 backdrop-blur-xl border border-white/40 p-8 rounded-[3rem] shadow-premium hover:shadow-2xl transition-all duration-500">
        <div class="flex items-center justify-between mb-6">
          <div :class="stat.bg" class="w-14 h-14 rounded-[1.5rem] flex items-center justify-center shadow-inner transition-transform group-hover:scale-110">
            <component :is="stat.icon" :class="stat.color" class="w-7 h-7" />
          </div>
          <span class="text-4xl font-black text-slate-900 tracking-tighter">{{ stat.value }}</span>
        </div>
        <p class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ stat.label }}</p>
        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-transparent via-slate-200 to-transparent w-full opacity-50"></div>
      </div>
    </div>

    <!-- Live Board Section -->
    <div class="bg-white/70 backdrop-blur-2xl rounded-[4rem] border border-white/50 shadow-2xl overflow-hidden mb-12">
      <div class="p-10 border-b border-slate-100/50 flex items-center justify-between bg-white/30">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight mb-1">File d'attente Active</h2>
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Mise à jour en direct</span>
          </div>
        </div>
        <button @click="fetchQueue" class="w-14 h-14 bg-white rounded-2xl text-slate-400 hover:text-brand-600 shadow-sm border border-slate-100 flex items-center justify-center transition-all hover:rotate-180 active:scale-90">
          <RefreshCwIcon :class="{'animate-spin': isLoading}" class="w-6 h-6" />
        </button>
      </div>

      <div class="p-4 md:p-8">
        <!-- Tabbed Navigation -->
        <div class="flex gap-4 mb-8 border-b border-slate-100 pb-2 overflow-x-auto scrollbar-none">
          <button @click="activeTab = 'active'" class="flex items-center gap-2 pb-3 px-4 font-black text-[11px] uppercase tracking-[0.15em] transition-all relative shrink-0"
            :class="activeTab === 'active' ? 'text-brand-600' : 'text-slate-400 hover:text-slate-600'">
            <ClipboardListIcon class="w-4 h-4" />
            File Active ({{ activeQueue.length }})
            <span v-if="activeTab === 'active'" class="absolute bottom-0 left-0 right-0 h-1 bg-brand-600 rounded-full animate-in zoom-in"></span>
          </button>
          <button @click="activeTab = 'delivered'" class="flex items-center gap-2 pb-3 px-4 font-black text-[11px] uppercase tracking-[0.15em] transition-all relative shrink-0"
            :class="activeTab === 'delivered' ? 'text-brand-600' : 'text-slate-400 hover:text-slate-600'">
            <PackageCheckIcon class="w-4 h-4" />
            Livrées Aujourd'hui ({{ deliveredQueue.length }})
            <span v-if="activeTab === 'delivered'" class="absolute bottom-0 left-0 right-0 h-1 bg-brand-600 rounded-full animate-in zoom-in"></span>
          </button>
        </div>

        <div class="grid grid-cols-1 gap-6">
          <!-- Job Row (3D Style) -->
          <div v-for="job in (activeTab === 'active' ? activeQueue : deliveredQueue)" :key="job.id" 
            class="group relative bg-white border border-slate-100 rounded-[3rem] p-8 transition-all hover:shadow-xl hover:translate-x-2 duration-500 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            
            <!-- Position & 3D Ticket -->
            <div class="flex items-center gap-8">
              <div class="relative">
                <!-- 3D Look Ticket -->
                <div class="w-20 h-24 bg-slate-900 rounded-3xl flex flex-col items-center justify-center shadow-2xl transform transition-transform group-hover:rotate-6">
                   <div class="text-[10px] font-black text-brand-400 uppercase mb-1">Queue</div>
                   <div class="text-xl font-black text-white tracking-tighter">{{ job.queue_number }}</div>
                   <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-8 bg-[#f8fafc] rounded-full border-r border-slate-100/10"></div>
                   <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-8 bg-[#f8fafc] rounded-full border-l border-slate-100/10"></div>
                </div>
                <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-brand-600 rounded-2xl flex items-center justify-center text-white text-xs font-black shadow-lg border-4 border-white">
                  #{{ job.position }}
                </div>
              </div>

              <div>
                <h3 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">{{ job.client_name }}</h3>
                <div class="flex flex-wrap gap-2">
                  <div v-for="s in job.services" :key="s.id" 
                    class="flex items-center gap-3 px-4 py-2 rounded-2xl text-xs font-black border transition-all duration-300"
                    :class="s.is_done ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-200'">
                    <span class="w-5 h-5 flex items-center justify-center rounded-lg bg-white/50">{{ (s.quantity) }}</span>
                    <span>{{ s.label }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Progression & Status Center -->
            <div class="flex flex-col items-center gap-4 flex-1 lg:max-w-xs">
              <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden shadow-inner p-0.5">
                <div class="h-full rounded-full transition-all duration-1000 ease-out flex items-center justify-end pr-2"
                  :class="job.all_done ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-gradient-to-r from-brand-400 to-brand-600'"
                  :style="{ width: (job.services_done / job.services_total * 100) + '%' }">
                  <div class="w-1 h-1 bg-white/50 rounded-full"></div>
                </div>
              </div>
              <div class="flex flex-wrap items-center justify-center gap-3">
                <!-- Status Badge -->
                <span :class="statusClasses(job.status)" class="flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-black uppercase tracking-wider border shadow-sm transition-all duration-300">
                  <span class="w-2 h-2 rounded-full" :class="{'bg-amber-500 animate-pulse': job.status === 'waiting', 'bg-blue-500 animate-pulse': job.status === 'in_progress', 'bg-emerald-500': job.status === 'done', 'bg-slate-400': job.status === 'delivered'}"></span>
                  {{ statusLabel(job.status) }}
                </span>
                
                <!-- Arrival Badge -->
                <span class="flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-200/80 text-slate-700 rounded-2xl text-xs font-black shadow-sm">
                  <ClockIcon class="w-4 h-4 text-slate-400" />
                  <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mr-0.5">Reçu à</span>
                  <span>{{ job.waiting_since }}</span>
                </span>
                
                <!-- Waiting Badge -->
                <span class="flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-black border shadow-sm transition-all duration-300"
                  :class="job.waiting_minutes > 60 ? 'bg-rose-50 border-rose-200/60 text-rose-600' : 'bg-brand-50/50 border-brand-200/40 text-brand-600'">
                  <HourglassIcon :class="job.waiting_minutes > 60 ? 'text-rose-500 animate-pulse' : 'text-brand-500'" class="w-4 h-4" />
                  <span class="text-[9px] font-black uppercase tracking-widest mr-0.5" :class="job.waiting_minutes > 60 ? 'text-rose-400' : 'text-brand-400'">Attente</span>
                  <span>{{ formatWaiting(job.waiting_minutes) }}</span>
                </span>
              </div>
            </div>

            <!-- Actions Right -->
            <div class="flex items-center gap-3">
              <button v-if="job.status === 'done'" @click="deliverJob(job)" 
                class="relative h-16 px-8 bg-emerald-600 text-white rounded-[2rem] font-black uppercase tracking-widest text-[11px] overflow-hidden transition-all hover:scale-105 active:scale-95 shadow-xl shadow-emerald-600/20 group/btn">
                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform"></div>
                <span class="relative flex items-center gap-3">
                  <PackageCheckIcon class="w-5 h-5" /> Livrer le Travail
                </span>
              </button>
              
              <!-- Cancel Delivery Button -->
              <button v-if="job.status === 'delivered'" @click="undeliverJob(job)" 
                class="relative h-16 px-8 bg-slate-900 text-white rounded-[2rem] font-black uppercase tracking-widest text-[11px] overflow-hidden transition-all hover:scale-105 active:scale-95 shadow-xl shadow-slate-900/20 group/btn">
                <span class="relative flex items-center gap-3">
                  <ArrowLeftIcon class="w-5 h-5" /> Annuler livraison
                </span>
              </button>

              <button @click="openJobDetails(job)" class="w-16 h-16 bg-white border border-slate-100 text-brand-600 rounded-[2rem] flex items-center justify-center transition-all hover:bg-brand-50 hover:border-brand-200 active:scale-90 shadow-sm" title="Dossier Client & Factures">
                <EyeIcon class="w-6 h-6" />
              </button>

              <button @click="deleteJob(job)" class="w-16 h-16 bg-white border border-slate-100 text-slate-400 rounded-[2rem] flex items-center justify-center transition-all hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 active:scale-90 shadow-sm">
                <Trash2Icon class="w-6 h-6" />
              </button>
            </div>
          </div>

          <!-- Empty Active State -->
          <div v-if="!isLoading && activeTab === 'active' && activeQueue.length === 0" class="py-20 text-center animate-in fade-in duration-500">
            <div class="w-32 h-32 bg-slate-50 rounded-[3rem] flex items-center justify-center mx-auto mb-8 border border-slate-100 shadow-inner">
               <LayersIcon class="w-12 h-12 text-slate-200" />
            </div>
            <h3 class="text-2xl font-black text-slate-400">Aucune commande active</h3>
            <p class="text-sm font-bold text-slate-300 mt-2">Toutes les commandes en attente ou en cours ont été complétées.</p>
          </div>

          <!-- Empty Delivered State -->
          <div v-if="!isLoading && activeTab === 'delivered' && deliveredQueue.length === 0" class="py-20 text-center animate-in fade-in duration-500">
            <div class="w-32 h-32 bg-slate-50 rounded-[3rem] flex items-center justify-center mx-auto mb-8 border border-slate-100 shadow-inner">
               <PackageCheckIcon class="w-12 h-12 text-slate-200" />
            </div>
            <h3 class="text-2xl font-black text-slate-400">Aucune commande livrée aujourd'hui</h3>
            <p class="text-sm font-bold text-slate-300 mt-2">Les commandes livrées aux clients s'afficheront ici dans l'historique du jour.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD CLIENT MODAL (PREMIUM GLASS) -->
    <Transition name="scale">
      <div v-if="showAddModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/75">
        <div class="bg-white border border-slate-100 w-full max-w-2xl rounded-[3rem] shadow-[0_30px_70px_rgba(0,0,0,0.2)] overflow-hidden">
          <div class="p-10 border-b border-slate-100/50 flex items-center justify-between">
            <div>
              <h3 class="text-3xl font-black text-slate-900 tracking-tight">Ajouter à la Queue</h3>
              <p class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">Nouveau ticket opérationnel</p>
            </div>
            <button @click="showAddModal = false" class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 hover:text-slate-900 transition-all">
              <XIcon class="w-6 h-6" />
            </button>
          </div>

          <form @submit.prevent="submitJob" class="p-10 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <div class="space-y-3 relative">
              <label class="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Client *</label>
              <div class="relative">
                <UserIcon class="w-5 h-5 absolute left-5 top-1/2 transform -translate-y-1/2 text-slate-400" />
                <input type="text" v-model="clientSearch" @input="onClientInput" @focus="showClientDropdown = true" @blur="setTimeout(() => showClientDropdown = false, 200)" required placeholder="Chercher ou taper le nom..."
                  class="w-full pl-14 pr-5 py-5 bg-white/50 border border-slate-200 rounded-3xl font-bold text-lg focus:ring-8 focus:ring-brand-500/10 focus:border-brand-500 transition-all shadow-inner">
              </div>
              <!-- Autocomplete Dropdown -->
              <div v-if="showClientDropdown && filteredClients.length > 0" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden max-h-64 overflow-y-auto">
                <button v-for="c in filteredClients" :key="c.id" type="button" @mousedown.prevent="selectClient(c)"
                  class="w-full px-5 py-4 flex items-center justify-between hover:bg-brand-50 transition-colors text-left border-b border-slate-50 last:border-b-0">
                  <div>
                    <span class="font-black text-slate-900 text-sm">{{ c.name }}</span>
                    <span v-if="c.phone" class="ml-2 text-xs text-slate-400 font-bold">{{ c.phone }}</span>
                  </div>
                  <span v-if="c.total_credit > 0" class="text-[10px] font-black text-rose-500 bg-rose-50 px-2 py-1 rounded-lg">{{ c.total_credit }} DH</span>
                </button>
              </div>
            </div>

            <!-- Dynamic Services with Quantities -->
            <div class="space-y-4">
              <div class="flex items-center justify-between mb-4">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Services & Quantités *</label>
                <div class="flex gap-2">
                   <button v-for="q in [5, 10, 20]" :key="q" @click.prevent="defaultQty = q" type="button"
                     :class="defaultQty === q ? 'bg-brand-600 text-white shadow-lg' : 'bg-slate-100 text-slate-500'"
                     class="w-8 h-8 rounded-lg text-[10px] font-black transition-all">
                     {{ q }}
                   </button>
                </div>
              </div>
              
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <button v-for="s in quickServices" :key="s" type="button" 
                  @click="toggleFormService(s)"
                  :class="isServiceSelected(s) ? 'bg-brand-600 text-white scale-105 shadow-xl shadow-brand-500/30 ring-4 ring-brand-500/20' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-200'"
                  class="p-5 rounded-3xl text-xs font-black transition-all duration-300 relative group overflow-hidden">
                  {{ s }}
                  <div v-if="isServiceSelected(s)" class="absolute -top-1 -right-1 w-6 h-6 bg-white text-brand-600 rounded-full flex items-center justify-center border-2 border-brand-600 animate-in zoom-in">
                    <CheckIcon class="w-3 h-3" />
                  </div>
                </button>
              </div>

              <!-- Custom Services Entry -->
              <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100">
                <div class="flex gap-3 mb-4">
                  <input type="text" v-model="customService" placeholder="Autre tâche..." 
                    class="flex-1 p-4 bg-white border border-slate-200 rounded-2xl font-bold text-sm focus:ring-4 focus:ring-brand-500/10">
                  <input type="number" v-model="customQty" class="w-20 p-4 bg-white border border-slate-200 rounded-2xl font-bold text-sm text-center">
                  <button @click.prevent="addCustomService" class="w-14 h-14 bg-slate-900 text-white rounded-2xl hover:bg-slate-800 transition-all active:scale-90">
                    <PlusIcon class="w-6 h-6 mx-auto" />
                  </button>
                </div>
                
                <div class="space-y-2">
                   <div v-for="(s, idx) in form.services" :key="idx" 
                    class="flex items-center justify-between bg-white px-5 py-3 rounded-2xl border border-slate-100 shadow-sm animate-in slide-in-from-left duration-300">
                     <div class="flex flex-col gap-4 flex-1">
                        <div class="flex items-center gap-4">
                           <div class="flex items-center bg-slate-100 rounded-xl overflow-hidden p-1 shadow-inner">
                              <button @click.prevent="s.quantity = Math.max(1, s.quantity - 1)" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:bg-white hover:text-brand-600 transition-all rounded-lg">
                                 <span class="text-2xl font-black">-</span>
                              </button>
                              <input type="number" v-model="s.quantity" class="w-16 text-center font-black text-brand-600 bg-transparent border-none focus:ring-0">
                              <button @click.prevent="s.quantity++" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:bg-white hover:text-brand-600 transition-all rounded-lg">
                                 <span class="text-2xl font-black">+</span>
                              </button>
                           </div>
                           <span class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">{{ s.label }}</span>
                        </div>
                        
                        <div class="flex gap-3">
                           <div class="flex-1 space-y-1">
                              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Matière</p>
                              <input type="text" v-model="s.material_type" placeholder="Ex: MDF" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold focus:ring-4 focus:ring-brand-500/10">
                           </div>
                           <div class="flex-1 space-y-1">
                              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Couleur / Décor</p>
                              <input type="text" v-model="s.material_color" placeholder="Ex: Blanc" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold focus:ring-4 focus:ring-brand-500/10">
                           </div>
                        </div>
                     </div>
                     <button @click="form.services.splice(idx, 1)" class="text-slate-300 hover:text-rose-500 transition-colors">
                       <XIcon class="w-4 h-4" />
                     </button>
                   </div>
                </div>
              </div>
            </div>

            <div class="space-y-3">
              <label class="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Notes Internes</label>
              <textarea v-model="form.notes" rows="3" placeholder="Ex: Livraison urgente, bois fragile..."
                class="w-full p-5 bg-white/50 border border-slate-200 rounded-3xl font-bold focus:ring-8 focus:ring-brand-500/10 resize-none shadow-inner"></textarea>
            </div>

            <button type="submit" :disabled="isSubmitting" class="w-full relative py-6 bg-slate-900 rounded-[2.5rem] overflow-hidden group shadow-2xl shadow-slate-900/40 active:scale-95 transition-all">
              <div class="absolute inset-0 bg-gradient-to-r from-brand-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <span class="relative text-white font-black uppercase tracking-[0.3em] text-xs">
                <span v-if="!isSubmitting">Confirmer le Ticket</span>
                <Loader2Icon v-else class="w-6 h-6 animate-spin mx-auto" />
              </span>
            </button>
          </form>
        </div>
      </div>
    </Transition>

    <!-- JOB DOSSIER & CRM SLIDE-OVER (CRM VIEW) -->
    <Transition name="slide-right">
      <div v-if="selectedJobDossier" class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl bg-slate-50 shadow-2xl flex flex-col border-l border-slate-200" style="transform: translateX(0);">
        <!-- Drawer Header -->
        <div class="bg-white px-8 py-6 border-b border-slate-100 flex justify-between items-center z-10">
          <div class="flex items-center gap-4">
             <div class="w-12 h-12 bg-brand-50 rounded-2xl flex items-center justify-center text-brand-600 font-black text-xl">
               {{ selectedJobDossier.client_name.charAt(0) }}
             </div>
             <div>
               <h2 class="text-2xl font-black text-slate-900">{{ selectedJobDossier.client_name }}</h2>
               <p class="text-slate-500 font-bold text-sm flex items-center mt-1">
                 <PhoneIcon class="w-3.5 h-3.5 mr-1 text-slate-400" /> {{ selectedJobDossier.client_phone || 'Pas de téléphone' }}
               </p>
             </div>
          </div>
          <button @click="selectedJobDossier = null" class="p-2 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-200 transition-colors">
            <XIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Drawer Content -->
        <div class="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
          
          <!-- Workshop Queue Ticket Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-6">
            <div class="flex justify-between items-center">
              <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Ticket Atelier</span>
              <span class="px-3 py-1 bg-slate-900 text-white font-black text-xs rounded-lg uppercase tracking-wider">
                {{ selectedJobDossier.queue_number }}
              </span>
            </div>
            
            <div class="grid grid-cols-3 gap-4 border-y border-slate-50 py-4">
              <div class="text-center">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Position</p>
                <p class="text-lg font-black text-slate-800">#{{ selectedJobDossier.position }}</p>
              </div>
              <div class="text-center border-x border-slate-50">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Arrivée</p>
                <p class="text-lg font-black text-slate-800">{{ selectedJobDossier.waiting_since }}</p>
              </div>
              <div class="text-center">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Attente</p>
                <p class="text-lg font-black text-brand-600">{{ formatWaiting(selectedJobDossier.waiting_minutes) }}</p>
              </div>
            </div>

            <!-- Tasks list -->
            <div class="space-y-3">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Détails des Tâches</p>
              <div class="space-y-2">
                <div v-for="s in selectedJobDossier.services" :key="s.id" 
                  class="flex items-center justify-between p-4 rounded-2xl border text-xs font-bold transition-all"
                  :class="s.is_done ? 'bg-emerald-50/50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-slate-500 border-slate-200'">
                  <div class="flex items-center gap-3">
                    <span class="w-6 h-6 flex items-center justify-center rounded-lg bg-white/80 border shadow-sm font-black">{{ s.quantity }}</span>
                    <div>
                      <span class="font-black">{{ s.label }}</span>
                      <div class="flex gap-2 text-[9px] font-bold text-slate-400 mt-0.5">
                        <span v-if="s.material_type">Type: {{ s.material_type }}</span>
                        <span v-if="s.material_color">Couleur: {{ s.material_color }}</span>
                      </div>
                    </div>
                  </div>
                  <span v-if="s.is_done" class="text-[9px] font-black bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded uppercase tracking-wider">
                    Fait par {{ s.done_by || 'Ouvrier' }}
                  </span>
                  <span v-else class="text-[9px] font-black bg-amber-100 text-amber-800 px-2 py-0.5 rounded uppercase tracking-wider">
                    En attente
                  </span>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="selectedJobDossier.notes" class="bg-amber-50/50 border border-amber-100 rounded-2xl p-4 text-xs font-bold text-slate-600">
              <p class="text-[9px] font-black text-amber-800 uppercase tracking-widest mb-1">Notes Internes</p>
              {{ selectedJobDossier.notes }}
            </div>
          </div>

          <!-- CRM Lookup (Financial Details) -->
          <div class="space-y-6">
            <h3 class="text-lg font-black text-slate-800 flex items-center">
              <CreditCardIcon class="w-5 h-5 mr-2 text-brand-500" /> Informations Client (ERP)
            </h3>

            <div v-if="isFetchingClientHistory" class="py-12 text-center text-slate-400">
               <Loader2Icon class="w-8 h-8 animate-spin mx-auto mb-3" />
               <span class="text-xs font-bold">Recherche des dossiers liés...</span>
            </div>

            <div v-else-if="!clientDossier" class="p-8 text-center bg-white border border-dashed border-slate-200 rounded-3xl text-slate-400">
               <p class="font-bold text-sm">Ce client n'est pas enregistré dans le répertoire.</p>
               <p class="text-xs mt-1">Les tickets ajoutés manuellement sans profil ERP n'affichent pas de factures.</p>
            </div>

            <div v-else class="space-y-6">
              <!-- CRM Statistics Cards -->
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative group overflow-hidden">
                   <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Crédit / Dette</p>
                   <h3 class="text-2xl font-black" :class="clientDossier.client.total_credit > 0 ? 'text-red-500' : 'text-emerald-500'">
                     {{ clientDossier.client.total_credit }} DH
                   </h3>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                   <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nombre de Factures</p>
                   <h3 class="text-2xl font-black text-slate-800">
                     {{ clientDossier.orders.length }}
                   </h3>
                </div>
              </div>

              <!-- Factures List inside Workshop details -->
              <div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 ml-1">Dernières Factures</h4>
                <div class="space-y-3">
                  <div v-for="order in clientDossier.orders.slice(0, 3)" :key="order.id" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-3">
                    <div class="flex justify-between items-center border-b border-slate-50 pb-2">
                      <span class="text-[10px] font-black text-slate-400">
                        {{ new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                      </span>
                      <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 font-black text-[10px] rounded uppercase tracking-wider">
                        Facture #{{ order.id }}
                      </span>
                    </div>
                    
                    <div class="flex justify-between items-center pt-1 text-xs">
                      <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase">Total</p>
                        <p class="font-black text-slate-800">{{ order.total_sell_price }} DH</p>
                      </div>
                      <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase">Payé</p>
                        <p class="font-black text-emerald-600">{{ order.amount_paid }} DH</p>
                      </div>
                      <div class="text-right">
                        <p class="text-[9px] font-black text-slate-400 uppercase">{{ (Number(order.total_sell_price) - Number(order.amount_paid)) > 0 ? 'Reste' : 'Surplus' }}</p>
                        <p class="font-black" :class="(Number(order.total_sell_price) - Number(order.amount_paid)) > 0 ? 'text-red-500' : 'text-emerald-500'">
                          {{ Math.abs(Number(order.total_sell_price) - Number(order.amount_paid)).toFixed(2) }} DH
                        </p>
                      </div>
                    </div>

                    <!-- Invoice Lines (Toggled / Expanded Details) -->
                    <div v-if="expandedOrders[order.id]" class="mt-2 pt-2 border-t border-slate-50 space-y-1.5 animate-in slide-in-from-top duration-300 text-xs">
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Détails des articles</p>
                      <div class="bg-slate-50 rounded-xl p-3 space-y-2">
                        <div v-for="line in order.lines" :key="line.id" class="flex justify-between items-center text-[11px] text-slate-600 font-bold">
                          <div class="flex items-center gap-1.5">
                            <span class="px-1 py-0.2 bg-slate-200 text-slate-700 font-black rounded text-[9px]">x{{ Number(line.quantity) }}</span>
                            <span>{{ getLineItemName(line) }}</span>
                            <span class="text-[9px] text-slate-400 font-normal">(P.U: {{ Number(line.unit_sell_price).toFixed(2) }} DH)</span>
                          </div>
                          <span class="font-black text-slate-800">{{ Number(line.total_line_sell).toFixed(2) }} DH</span>
                        </div>
                      </div>
                    </div>

                    <!-- Details Toggle & Print Action Row -->
                    <div class="mt-1 pt-2 border-t border-slate-50 flex justify-between items-center gap-2">
                      <div class="flex items-center gap-3">
                        <button @click="toggleOrderDetails(order.id)" class="text-indigo-600 hover:text-indigo-700 font-black text-[10px] flex items-center gap-1 transition-colors">
                          <EyeIcon class="w-3.5 h-3.5" />
                          {{ expandedOrders[order.id] ? 'Masquer Articles' : 'Voir Articles' }}
                        </button>
                        
                        <button @click="printOrderInvoice(order)" class="text-slate-500 hover:text-slate-700 font-black text-[10px] flex items-center gap-1 transition-colors">
                          <PrinterIcon class="w-3.5 h-3.5" />
                          Imprimer
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Dimmer for Drawer -->
    <Transition name="fade">
      <div v-if="selectedJobDossier" @click="selectedJobDossier = null" class="fixed inset-0 z-40 bg-slate-900/20 backdrop-blur-sm"></div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  PlusIcon, RefreshCwIcon, Trash2Icon, ClockIcon, 
  CheckCircleIcon, XIcon, Loader2Icon, ActivityIcon,
  UsersIcon, HourglassIcon, CheckIcon, PackageCheckIcon,
  LayersIcon, InfoIcon, ClipboardListIcon, ArrowLeftIcon,
  EyeIcon, PhoneIcon, CalendarIcon, CreditCardIcon,
  UserIcon, FileTextIcon, PrinterIcon
} from 'lucide-vue-next';

const queue = ref([]);
const isLoading = ref(false);
const showAddModal = ref(false);
const isSubmitting = ref(false);
const customService = ref('');
const customQty = ref(1);
const defaultQty = ref(1);
let pollInterval = null;

// Client autocomplete
const allClients = ref([]);
const clientSearch = ref('');
const showClientDropdown = ref(false);

const filteredClients = computed(() => {
  if (!clientSearch.value.trim()) return [];
  const q = clientSearch.value.toLowerCase().trim();
  return allClients.value.filter(c => 
    c.name.toLowerCase().includes(q) || (c.phone && c.phone.includes(q))
  ).slice(0, 8);
});

const selectClient = (client) => {
  form.value.client_name = client.name;
  form.value.client_phone = client.phone || '';
  clientSearch.value = client.name;
  showClientDropdown.value = false;
};

const onClientInput = () => {
  form.value.client_name = clientSearch.value;
  form.value.client_phone = '';
  showClientDropdown.value = true;
};

const fetchClients = async () => {
  try {
    const res = await axios.get('/api/admin/clients');
    allClients.value = res.data;
  } catch (e) {
    console.error('Failed to load clients', e);
  }
};

// Tabbed queue system
const activeTab = ref('active');

const activeQueue = computed(() => {
  return queue.value.filter(j => j.status !== 'delivered');
});

const deliveredQueue = computed(() => {
  return queue.value.filter(j => j.status === 'delivered');
});

// Sliding Job & CRM Drawer State
const selectedJobDossier = ref(null);
const clientDossier = ref(null);
const isFetchingClientHistory = ref(false);

const openJobDetails = async (job) => {
  selectedJobDossier.value = job;
  clientDossier.value = null;
  isFetchingClientHistory.value = true;
  try {
    // 1. Fetch all clients to find a match by phone or name
    const clientsRes = await axios.get('/api/admin/clients');
    const allClients = Array.isArray(clientsRes.data) 
      ? clientsRes.data 
      : (clientsRes.data.data || clientsRes.data.clients || []);
      
    // Try matching by phone first, then by name
    let matchedClient = allClients.find(c => c.phone && job.client_phone && c.phone.trim() === job.client_phone.trim());
    if (!matchedClient) {
      const jobNameClean = job.client_name.toLowerCase().trim();
      matchedClient = allClients.find(c => c.name && c.name.toLowerCase().trim() === jobNameClean);
    }
    
    // 2. If matched, fetch their full invoice history
    if (matchedClient) {
      const historyRes = await axios.get(`/api/clients/${matchedClient.id}/history`);
      clientDossier.value = historyRes.data;
    }
  } catch (error) {
    console.error("Error fetching client history in workshop queue", error);
  } finally {
    isFetchingClientHistory.value = false;
  }
};

// Expanded invoice details state
const expandedOrders = ref({});
const toggleOrderDetails = (orderId) => {
  expandedOrders.value[orderId] = !expandedOrders.value[orderId];
};

const getLineItemName = (line) => {
  if (line.label && line.label !== 'null' && line.label.trim() !== '') return line.label;
  
  if (line.item) {
    if (line.item.name) return line.item.name;
    if (line.item_type === 'App\\Models\\StockPanel') {
      return `Panneau ${line.item.type || ''} ${line.item.size_x || ''}x${line.item.size_y || ''}`.trim();
    }
    if (line.item_type === 'App\\Models\\StockCanto') {
      return `Bandchant ${line.item.color_name || line.item.name || ''} [${line.item.finish_type || 'STD'}]`.trim();
    }
    if (line.item_type === 'App\\Models\\Consumable') {
      return `Article ${line.item.name || ''}`.trim();
    }
    if (line.item_type === 'App\\Models\\Service') {
      return `${line.item.name || 'Service'}`.trim();
    }
  }
  
  if (line.item_type) {
    const shortType = line.item_type.replace('App\\Models\\', '');
    return `${shortType} ${line.item_id ? '#' + line.item_id : ''}`.trim();
  }
  
  return 'Article Générique';
};

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

const printOrderInvoice = (order) => {
  const clientName = clientDossier.value?.client?.name || order.client?.name || 'Client';
  const dateStr = new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  
  const linesHtml = (order.lines || []).map(line => {
    const itemName = getLineItemName(line);
    const qty = Number(line.quantity);
    const pu = Number(line.unit_sell_price);
    const total = Number(line.total_line_sell);
    return `<tr><td>${formatItemName(itemName)}</td><td class="text-right">${qty.toFixed(2)}</td><td class="text-right">${pu.toFixed(2)} DH</td><td class="text-right" style="color:#0f172a;font-weight:800">${total.toFixed(2)} DH</td></tr>`;
  }).join('');
  
  const total = Number(order.total_sell_price);
  const paid = Number(order.amount_paid) || 0;
  const reste = total - paid;

  const settings = window.appSettings || {};
  const companyName = settings.company_name || 'Mon Entreprise';
  const companyPhone = settings.company_phone || '';
  const footerText = settings.invoice_footer_text || 'Merci pour votre confiance !';
  const rcIceHtml = (settings.company_rc || settings.company_ice) ? `<p style="font-size:10px;color:#64748b;margin-top:5px;font-weight:bold">${settings.company_ice ? 'ICE: ' + settings.company_ice : ''} ${settings.company_rc ? 'RC: ' + settings.company_rc : ''}</p>` : '';
  const logoHtml = settings.company_logo ? `<img src="/storage/${settings.company_logo}" style="height:80px;width:80px;object-fit:contain">` : '';

  const w = window.open('', '', 'height=800,width=800');
  w.document.write('<html><head><title>Facture #' + order.id + '</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet"><style>body{font-family:\'Inter\',sans-serif;color:#1e293b;padding:40px;max-width:800px;margin:0 auto}h1{font-size:28px;font-weight:800;margin:0}.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #e2e8f0;padding-bottom:20px;margin-bottom:30px}.inv-num{font-size:14px;font-weight:800;color:#0f172a}.client-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:30px}table{width:100%;border-collapse:collapse;margin-bottom:30px}th{text-align:left;padding:12px 16px;font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:2px solid #e2e8f0}td{padding:16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:600;color:#475569}.text-right{text-align:right}.totals{width:320px;margin-left:auto;background:#f8fafc;border-radius:12px;padding:20px;border:1px solid #e2e8f0}.total-line{display:flex;justify-content:space-between;margin-bottom:12px;font-size:14px;font-weight:600;color:#64748b}.total-final{display:flex;justify-content:space-between;margin-top:15px;padding-top:15px;border-top:2px dashed #cbd5e1;font-size:20px;font-weight:800;color:#0f172a}.footer{margin-top:50px;text-align:center;font-size:12px;color:#94a3b8;font-weight:600;padding-top:20px;border-top:1px solid #f1f5f9}@media print{body{padding:0}@page{margin:1cm}}</style></head><body><div class="header"><div style="display:flex;align-items:center;gap:20px">' + logoHtml + '<div><h1>' + companyName + '</h1>' + rcIceHtml + '</div></div><div style="text-align:right"><h2 style="font-size:28px;font-weight:800;text-transform:uppercase;color:#cbd5e1;margin:0">Facture</h2><p class="inv-num">N° FACT-' + order.id + '</p><p style="font-size:12px;font-weight:600;color:#64748b;margin-top:5px">' + dateStr + '</p></div></div><div class="client-box"><p style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin:0 0 5px">Facturé à</p><p style="font-size:18px;font-weight:800;color:#0f172a;margin:0">' + clientName + '</p></div><table><thead><tr><th>Désignation</th><th class="text-right">Qté</th><th class="text-right">P.U</th><th class="text-right">Total</th></tr></thead><tbody>' + linesHtml + '</tbody></table><div class="totals"><div class="total-line"><span>Total Facture</span><span style="color:#0f172a;font-weight:800">' + total.toFixed(2) + ' DH</span></div><div class="total-line"><span>Avance (Payé)</span><span style="color:#10b981;font-weight:800">' + paid.toFixed(2) + ' DH</span></div><div class="total-final"><span>Reste à Payer</span><span style="color:' + (reste > 0 ? '#ef4444' : '#10b981') + '">' + Math.abs(reste).toFixed(2) + ' DH</span></div></div><div class="footer">' + footerText + '<br>' + companyName + (companyPhone ? ' - Contact: ' + companyPhone : '') + '</div></body></html>');
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); }, 500);
};

const form = ref({
  client_name: '',
  client_phone: '',
  services: [],
  notes: ''
});

const quickServices = ['Découpe', 'Pose Canto', 'Ponçage', 'Finition', 'Assemblage', 'Perçage'];

const stats = computed(() => [
  { label: 'En attente', value: queue.value.filter(j => j.status === 'waiting').length, icon: HourglassIcon, bg: 'bg-amber-100/50', color: 'text-amber-600' },
  { label: 'Actifs', value: queue.value.filter(j => j.status === 'in_progress').length, icon: ActivityIcon, bg: 'bg-blue-100/50', color: 'text-blue-600' },
  { label: 'Prêts', value: queue.value.filter(j => j.status === 'done').length, icon: CheckCircleIcon, bg: 'bg-emerald-100/50', color: 'text-emerald-600' },
  { label: 'Total Journée', value: queue.value.length, icon: UsersIcon, bg: 'bg-slate-900', color: 'text-white' }
]);



const isServiceSelected = (label) => form.value.services.some(s => s.label === label);

const toggleFormService = (label) => {
  const idx = form.value.services.findIndex(s => s.label === label);
  if (idx > -1) {
    form.value.services.splice(idx, 1);
  } else {
    form.value.services.push({ label, quantity: defaultQty.value, unit: 'u' });
  }
};

const addCustomService = () => {
  if (customService.value.trim()) {
    form.value.services.push({ 
      label: customService.value.trim(), 
      quantity: customQty.value, 
      unit: 'u' 
    });
    customService.value = '';
    customQty.value = 1;
  }
};

const submitJob = async () => {
  if (form.value.services.length === 0) return toast.warning('Veuillez ajouter au moins une tâche.');
  isSubmitting.value = true;
  try {
    await axios.post('/api/admin/workshop-queue', form.value);
    showAddModal.value = false;
    form.value = { client_name: '', client_phone: '', services: [], notes: '' };
    clientSearch.value = '';
    fetchQueue();
  } catch (error) {
    toast.error(error.response?.data?.error || 'Erreur lors de l\'ajout.');
  } finally {
    isSubmitting.value = false;
  }
};

const deliverJob = async (job) => {
  if (!confirm(`Confirmer la livraison pour ${job.client_name} ?`)) return;
  try {
    await axios.post(`/api/admin/workshop-queue/${job.id}/deliver`);
    fetchQueue();
  } catch (error) {
    toast.error('Erreur lors de la livraison.');
  }
};

const undeliverJob = async (job) => {
  if (!confirm(`Annuler la livraison pour ${job.client_name} ?`)) return;
  try {
    await axios.post(`/api/admin/workshop-queue/${job.id}/undeliver`);
    fetchQueue();
  } catch (error) {
    toast.error('Erreur lors de l\'annulation de la livraison.');
  }
};

const deleteJob = async (job) => {
  if (!confirm(`Supprimer ${job.queue_number} de la liste ?`)) return;
  try {
    await axios.delete(`/api/admin/workshop-queue/${job.id}`);
    fetchQueue();
  } catch (error) {
    toast.error('Erreur lors de la suppression.');
  }
};

const formatWaiting = (mins) => {
  if (mins < 60) return `${mins} min`;
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return `${h}h ${m}m`;
};

const statusLabel = (s) => ({
  waiting: 'Attente',
  in_progress: 'Exécution',
  done: 'Prêt',
  delivered: 'Livré'
}[s] || s);

const statusClasses = (s) => ({
  waiting: 'bg-amber-50 text-amber-600 border-amber-100',
  in_progress: 'bg-blue-50 text-blue-600 border-blue-100',
  done: 'bg-emerald-50 text-emerald-600 border-emerald-100',
  delivered: 'bg-slate-100 text-slate-500 border-slate-200'
}[s] || 'bg-slate-50 text-slate-500 border-slate-200');

const fetchQueue = async (force = false) => {
  if (isLoading.value && !force) return;
  isLoading.value = true;
  try {
    const response = await axios.get('/api/admin/workshop-queue');
    queue.value = response.data;
  } catch (error) {
    console.error('Fetch error', error);
  } finally {
    isLoading.value = false;
  }
};

const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    fetchQueue(true);
    startPolling();
  } else {
    stopPolling();
  }
};

const startPolling = () => {
  stopPolling();
  pollInterval = setInterval(() => fetchQueue(), 60000); // 60s
};

const stopPolling = () => {
  if (pollInterval) clearInterval(pollInterval);
};

onMounted(() => {
  fetchQueue();
  fetchClients();
  startPolling();
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
  stopPolling();
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<style scoped>

.shadow-premium {
  box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #e2e8f0;
  border-radius: 9999px;
}

.scale-enter-active, .scale-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.scale-enter-from, .scale-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* 3D Ticket inner effect */
.shadow-inner-3d {
  box-shadow: inset 0 2px 10px rgba(255,255,255,0.1);
}
</style>
