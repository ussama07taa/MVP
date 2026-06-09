<template>
  <div class="min-h-screen bg-[#f8fafc] font-sans relative overflow-hidden">
    
    <!-- Ambient Background (Master UI) -->
    <div class="fixed top-0 left-0 w-full h-[600px] pointer-events-none z-0">
      <div class="absolute -top-32 -right-32 w-[35rem] h-[35rem] bg-indigo-100/80 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
      <div class="absolute top-20 -left-32 w-[30rem] h-[30rem] bg-brand-100/80 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
    </div>

    <div class="relative z-10 w-full max-w-[1400px] mx-auto p-4 lg:p-8">
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-6">
          <div class="w-16 h-16 bg-white rounded-[1.5rem] flex items-center justify-center shadow-sm border border-slate-100 shrink-0 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-500/10 to-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <UsersIcon class="w-8 h-8 text-brand-600 relative z-10 group-hover:scale-110 transition-transform duration-500" />
          </div>
          <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-1">Répertoire Clients</h1>
            <p class="text-slate-500 font-medium text-sm">Gérez votre base client et suivez les crédits en cours.</p>
          </div>
        </div>
      <div class="flex items-center gap-4 w-full md:w-auto">
        <button @click="loadClients" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="btn-secondary !p-3"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 transition-transform duration-500" />
        </button>

        <div class="relative flex-1 md:w-64">
          <UserSearchIcon class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" />
          <input v-model="searchQuery" type="text" placeholder="Rechercher un client..." 
            class="input-premium pl-9 py-2 text-sm">
        </div>

        <button v-if="currentUser?.role === 'admin'" @click="exportData('clients')" class="btn-success">
          <FileDownIcon class="w-4 h-4 mr-2" /> Exporter
        </button>

        <button @click="openAddForm" class="btn-primary">
          <UserPlusIcon class="w-4 h-4 mr-2" /> {{ showAddForm ? 'Fermer' : 'Nouveau Client' }}
        </button>
      </div>
    </div>

    <!-- Client Dossier Slide-over (CRM View) -->
    <Teleport to="body">
      <transition name="slide-right">
        <div v-if="selectedClientDossier" class="fixed inset-y-0 right-0 z-[100] w-full max-w-4xl mx-0 sm:mx-auto bg-[#FDFCFE] shadow-2xl flex flex-col border-l border-slate-200/50" style="transform: translateX(0);">
        <!-- Drawer Header (HERO STYLE) -->
        <div class="relative overflow-hidden bg-white px-8 py-10 border-b border-slate-100/80 z-10">
          <!-- Abstract Background Decor -->
          <div class="absolute -top-12 -right-12 w-48 h-48 bg-brand-50/50 rounded-full blur-3xl opacity-60"></div>
          <div class="absolute top-20 -left-10 w-32 h-32 bg-indigo-50/40 rounded-full blur-2xl opacity-40"></div>
          
          <div class="relative flex justify-between items-start">
            <div class="flex items-center gap-6">
               <div class="w-20 h-20 bg-gradient-to-br from-brand-600 to-indigo-700 rounded-[2rem] flex items-center justify-center text-white font-black text-3xl shadow-xl shadow-brand-200 transform hover:rotate-6 transition-transform duration-300">
                 {{ selectedClientDossier.client.name.charAt(0) }}
               </div>
               <div>
                 <div class="flex items-center gap-2 mb-1">
                   <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ selectedClientDossier.client.name }}</h2>
                   <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-100/50">VIP</span>
                 </div>
                 <p class="text-slate-400 font-bold text-sm flex flex-wrap items-center gap-x-5 gap-y-1">
                   <span class="flex items-center"><PhoneIcon class="w-3.5 h-3.5 mr-1.5 text-brand-500" /> {{ selectedClientDossier.client.phone || 'N/A' }}</span>
                   <span v-if="selectedClientDossier.client.city" class="flex items-center"><MapPinIcon class="w-3.5 h-3.5 mr-1.5 text-brand-500" /> {{ selectedClientDossier.client.city }}</span>
                   <span class="flex items-center bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100">ID: #CL-{{ selectedClientDossier.client.id }}</span>
                 </p>
               </div>
            </div>
            <button @click="selectedClientDossier = null" class="p-3 bg-slate-50 text-slate-400 rounded-2xl hover:bg-slate-100 hover:text-slate-600 transition-all active:scale-90 shadow-sm border border-slate-100">
              <XIcon class="w-6 h-6" />
            </button>
          </div>
        </div>

        <!-- Drawer Content -->
        <div class="flex-1 overflow-y-auto p-8 space-y-10 bg-gradient-to-b from-white to-slate-50/30">
          
          <!-- Stats Grid (PRO MAX) -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- CA Total -->
            <div class="relative p-5 rounded-[2.5rem] bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all group overflow-hidden">
               <div class="absolute -right-2 -bottom-2 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity">
                 <HistoryIcon class="w-16 h-16 text-slate-900" />
               </div>
               <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                 <RotateCwIcon class="w-4 h-4" />
               </div>
               <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] mb-1">Chiffre d'Affaires</p>
               <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ safeNumber(selectedClientDossier.stats?.total_revenue) }} <span class="text-xs text-slate-400">DH</span></h3>
            </div>

            <!-- Crédit / Dette -->
            <div class="relative p-5 rounded-[2.5rem] bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all group overflow-hidden">
               <div class="absolute -right-2 -bottom-2 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity">
                 <AlertCircleIcon class="w-16 h-16 text-red-900" />
               </div>
               <div class="w-8 h-8 rounded-xl flex items-center justify-center mb-3" :class="selectedClientDossier.client.total_credit > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'">
                 <CreditCardIcon class="w-4 h-4" />
               </div>
               <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] mb-1">Dette Actuelle</p>
               <h3 class="text-xl font-black tracking-tight" :class="selectedClientDossier.client.total_credit > 0 ? 'text-rose-600' : 'text-emerald-700'">
                 {{ safeNumber(selectedClientDossier.client.total_credit) }} <span class="text-xs opacity-50">DH</span>
               </h3>
               <button v-if="currentUser?.role === 'admin'" @click="recalculateCredit" title="Recalculer" 
                       class="absolute top-4 right-4 p-1.5 bg-slate-50 text-slate-400 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white hover:shadow-sm border border-slate-100">
                 <RotateCwIcon class="w-3 h-3" />
               </button>
            </div>

            <!-- Dernière Visite -->
            <div class="relative p-5 rounded-[2.5rem] bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all group overflow-hidden">
               <div class="absolute -right-2 -bottom-2 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity">
                 <CalendarIcon class="w-16 h-16 text-slate-900" />
               </div>
               <div class="w-8 h-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center mb-3">
                 <CalendarIcon class="w-4 h-4" />
               </div>
               <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] mb-1">Dernière Visite</p>
               <h3 class="text-sm font-black text-slate-800 tracking-tight">
                 {{ selectedClientDossier.stats?.last_order_date ? formatDate(selectedClientDossier.stats.last_order_date) : 'Aucune' }}
               </h3>
            </div>

            <!-- Client Depuis -->
            <div class="relative p-5 rounded-[2.5rem] bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all group overflow-hidden">
               <div class="absolute -right-2 -bottom-2 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity">
                 <UserIcon class="w-16 h-16 text-slate-900" />
               </div>
               <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center mb-3">
                 <UserIcon class="w-4 h-4" />
               </div>
               <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] mb-1">Fidélité</p>
               <h3 class="text-sm font-black text-slate-800 tracking-tight">
                 {{ selectedClientDossier.stats?.member_since ? formatDate(selectedClientDossier.stats.member_since) : 'Nouveau' }}
               </h3>
            </div>
          </div>

          <!-- Quick Actions (HERO BUTTONS) -->
          <div class="flex gap-4 flex-wrap items-center">
            <button v-if="selectedClientDossier.client.total_credit > 0" @click="openGlobalPaymentModal" 
                    class="group relative bg-brand-600 text-white px-8 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-brand-100 hover:bg-brand-700 transition-all active:scale-95 flex items-center">
              <span class="absolute inset-x-0 bottom-0 h-1 bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left rounded-b-2xl"></span>
              <CreditCardIcon class="w-4 h-4 mr-2.5" /> RÉGLER LA DETTE
            </button>
            
            <a v-if="selectedClientDossier.client.phone" :href="'tel:' + selectedClientDossier.client.phone" 
               class="bg-blue-600 text-white px-8 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95 flex items-center">
              <PhoneCallIcon class="w-4 h-4 mr-2.5" /> Appeler le client
            </a>
            
            <button class="p-3.5 bg-white border border-slate-100 text-slate-400 rounded-2xl hover:text-brand-600 hover:border-brand-100 hover:bg-brand-50/50 transition-all shadow-sm">
               <HistoryIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Tab System (PILL STYLE) -->
          <div class="bg-slate-100/50 p-1.5 rounded-[2rem] border border-slate-200/50 flex gap-1 inline-flex w-full xl:w-auto">
            <button v-for="tab in dossierTabs" :key="tab.value" @click="activeDossierTab = tab.value"
              :class="activeDossierTab === tab.value ? 'bg-white text-brand-600 shadow-sm border-slate-200/60' : 'text-slate-400 border-transparent hover:text-slate-600'"
              class="flex-1 xl:flex-none px-6 py-2.5 text-[10px] font-black uppercase tracking-widest border transition-all rounded-[1.5rem] flex items-center justify-center gap-2">
              {{ tab.label }}
              <span v-if="tab.count !== undefined" :class="activeDossierTab === tab.value ? 'bg-brand-50 text-brand-600' : 'bg-slate-200 text-slate-500'" 
                    class="px-2 py-0.5 rounded-lg text-[9px] font-black transition-colors">{{ tab.count }}</span>
            </button>
          </div>

          <!-- TAB: Profil -->
          <div v-if="activeDossierTab === 'profile'" class="animate-in fade-in duration-300">
            <div v-if="currentUser?.role === 'admin'" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6">
              <h3 class="text-sm font-black text-slate-800 flex items-center uppercase tracking-widest">
                <Edit2Icon class="w-4 h-4 mr-2.5 text-brand-500" /> Modifier le Profil
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nom Complet</label>
                  <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200/60 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-bold text-sm text-slate-700 transition-all">
                </div>
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Mobile / WhatsApp</label>
                  <input v-model="form.phone" type="text" class="w-full bg-slate-50 border-slate-200/60 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-bold text-sm text-slate-700 transition-all">
                </div>
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Adresse de livraison</label>
                  <input v-model="form.address" type="text" class="w-full bg-slate-50 border-slate-200/60 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-bold text-sm text-slate-700 transition-all" placeholder="Rue, Quartier...">
                </div>
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Ville</label>
                  <input v-model="form.city" type="text" class="w-full bg-slate-50 border-slate-200/60 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-bold text-sm text-slate-700 transition-all" placeholder="Ville...">
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Observations internes</label>
                <textarea v-model="form.notes" rows="3" class="w-full bg-slate-50 border-slate-200/60 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-bold text-sm text-slate-700 transition-all resize-none" placeholder="VIP, remarques, préférences..."></textarea>
              </div>
              <div class="flex justify-end">
                 <button @click="saveClient" class="bg-brand-600 text-white px-10 py-3.5 rounded-[1.5rem] font-black shadow-lg shadow-brand-100 hover:bg-brand-700 transition-all text-xs uppercase tracking-widest active:scale-95">
                   Enregistrer
                 </button>
              </div>
            </div>
            
            <div v-else class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-5">
              <div v-if="selectedClientDossier.client.address" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100">
                  <MapPinIcon class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Adresse</p>
                  <span class="text-sm font-bold text-slate-700">{{ selectedClientDossier.client.address }}<span v-if="selectedClientDossier.client.city">, {{ selectedClientDossier.client.city }}</span></span>
                </div>
              </div>
              <div v-if="selectedClientDossier.client.notes" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 border border-amber-100">
                  <StickyNoteIcon class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Notes</p>
                  <span class="text-sm font-bold text-slate-700">{{ selectedClientDossier.client.notes }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: Factures (ADAPTIVE CARDS) -->
          <div v-if="activeDossierTab === 'factures'" class="animate-in slide-in-from-bottom-2 duration-300">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-sm font-black text-slate-800 flex items-center uppercase tracking-widest">
                <FileTextIcon class="w-4 h-4 mr-2.5 text-indigo-500" /> Historique des Factures
              </h3>
              <div class="relative">
                <CalendarIcon class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input v-model="orderDateFilter" type="date" class="bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-black text-slate-600 focus:ring-2 focus:ring-indigo-500 shadow-sm uppercase">
              </div>
            </div>
            
            <div v-if="filteredOrders.length === 0" class="p-12 text-center bg-white border border-dashed border-slate-200 rounded-[2.5rem] text-slate-400">
               <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                 <FileTextIcon class="w-8 h-8 opacity-20" />
               </div>
               <p class="font-black uppercase tracking-widest text-xs">Aucune facture</p>
            </div>

            <div v-else class="space-y-4">
               <div v-for="order in filteredOrders" :key="order.id" class="bg-white rounded-[2rem] border border-slate-100 shadow-sm transition-all hover:border-brand-200 hover:shadow-md group/card">
                 <div class="p-6">
                   <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 pb-5 border-b border-slate-50">
                     <div class="flex items-center text-slate-500 font-black text-[10px] uppercase tracking-[0.15em]">
                       <CalendarIcon class="w-4 h-4 mr-2.5 text-slate-400" /> {{ new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                     </div>
                     <div class="flex items-center gap-2">
                       <span class="px-5 py-2 bg-slate-900 text-white font-black text-xs rounded-xl border border-slate-800 uppercase tracking-[0.2em] shadow-xl shadow-slate-900/10 transform hover:scale-105 transition-transform cursor-default">
                         FAC-{{ order.id }}
                       </span>
                       <span :class="Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid) > 0.01 ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'" 
                             class="px-3 py-1 font-black text-[9px] rounded-lg border uppercase tracking-[0.1em]">
                         {{ Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid) > 0.01 ? 'En Dette' : 'À Jour' }}
                       </span>
                       <span v-if="order.total_refunded_amount > 0" class="px-3 py-1 font-black text-[9px] rounded-lg border uppercase tracking-[0.1em] bg-rose-50 text-rose-600 border-rose-100">
                         Retour: {{ safeNumber(order.total_refunded_amount) }} DH
                       </span>
                     </div>
                   </div>
                   
                   <div class="grid grid-cols-3 gap-6">
                     <div>
                       <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ order.total_refunded_amount > 0 ? 'Montant Net' : 'Montant Total' }}</p>
                       <div class="flex flex-col">
                         <span v-if="order.total_refunded_amount > 0" class="text-[10px] font-bold text-slate-300 line-through decoration-rose-400/50">{{ safeNumber(order.total_sell_price) }}</span>
                         <p class="font-black text-slate-800 text-lg tracking-tight">{{ safeNumber(order.net_total ?? order.total_sell_price) }} <span class="text-[10px] text-slate-400">DH</span></p>
                       </div>
                     </div>
                     <div>
                       <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Total Payé</p>
                       <p class="font-black text-emerald-600 text-lg tracking-tight">{{ safeNumber(order.amount_paid) }} <span class="text-[10px] opacity-50">DH</span></p>
                     </div>
                      <div class="text-right">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ (Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid)) > 0.01 ? 'Reste à régler' : 'Surplus' }}</p>
                        <p class="font-black text-lg tracking-tight" :class="(Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid)) > 0.01 ? 'text-red-500' : 'text-emerald-500'">
                          {{ safeNumber(Math.abs(Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid))) }} <span class="text-[10px] opacity-50">DH</span>
                        </p>
                      </div>
                   </div>
                 </div>

                  <div v-if="expandedOrders[order.id]" class="px-6 pb-6 animate-in slide-in-from-top-2 duration-200">
                    <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100/50">
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                        <ActivityIcon class="w-3 h-3 mr-2 text-indigo-500" /> Composition du panier
                      </p>
                      <div class="space-y-3">
                        <div v-for="(line, lineIdx) in groupOrderLines(order.lines)" :key="`${order.id}-${lineIdx}`" 
                             class="flex justify-between items-center text-xs animate-in fade-in slide-in-from-left-1 duration-300"
                             :class="line.quantity_returned >= line.quantity ? 'opacity-40 grayscale pointer-events-none select-none' : ''">
                          <div class="font-bold flex items-center gap-3">
                            <span class="w-9 h-6 bg-white text-slate-900 border border-slate-200 font-black rounded-lg flex items-center justify-center text-[10px] shadow-sm">x{{ Number(line.quantity) }}</span>
                            <div class="flex flex-col">
                              <span class="text-slate-700" :class="line.quantity_returned >= line.quantity ? 'line-through decoration-rose-500/50 decoration-2' : ''">{{ line.displayName }}</span>
                              <span v-if="line.quantity_returned > 0 && line.quantity_returned < line.quantity" class="text-[9px] text-rose-500 font-black flex items-center gap-1">
                                <RotateCwIcon class="w-2.5 h-2.5" /> -{{ line.quantity_returned }} Retourné
                              </span>
                            </div>
                            <span class="text-[10px] text-slate-400 bg-slate-100/50 px-2 py-0.5 rounded-md font-medium">{{ Number(line.unit_sell_price).toFixed(2) }} DH</span>
                          </div>
                          <div class="flex flex-col items-end">
                            <span class="font-black text-slate-900" :class="line.quantity_returned >= line.quantity ? 'line-through text-slate-300' : ''">{{ safeNumber(line.total_line_sell) }} <span class="text-[9px] font-bold text-slate-400">DH</span></span>
                            <span v-if="line.quantity_returned >= line.quantity" class="text-[8px] font-black uppercase text-rose-500 tracking-widest mt-1 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                               Totalement Retourné
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="px-6 py-4 bg-slate-50/30 rounded-b-[2rem] border-t border-slate-50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                       <button @click="toggleOrderDetails(order.id)" 
                               class="h-9 px-4 bg-white text-brand-600 font-black text-[10px] uppercase tracking-widest rounded-xl border border-slate-100 hover:border-brand-100 hover:bg-brand-50 transition-all flex items-center gap-2 shadow-sm active:scale-95">
                         <EyeIcon class="w-3 h-3" />
                         {{ expandedOrders[order.id] ? 'Masquer' : 'Détails' }}
                       </button>
                       <button @click="printOrderInvoice(order)" 
                               class="h-9 px-4 bg-white text-slate-500 font-black text-[10px] uppercase tracking-widest rounded-xl border border-slate-100 hover:text-slate-900 hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm active:scale-95">
                         <PrinterIcon class="w-3.5 h-3.5" /> Imprimer
                       </button>
                    </div>
                    <button v-if="(Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid)) > 0.01" 
                            @click="openPaymentModal(order)" 
                            class="h-9 px-5 bg-emerald-600 text-white font-black text-[10px] uppercase tracking-widest rounded-xl shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all flex items-center gap-2 active:scale-95">
                       <CreditCardIcon class="w-3.5 h-3.5" /> Encaisser
                    </button>
                  </div>
               </div>
            </div>
          </div>
          
          <!-- TAB: Relevé (Unified Ledger) -->
          <div v-if="activeDossierTab === 'ledger'" class="animate-in fade-in duration-300">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-sm font-black text-slate-800 flex items-center uppercase tracking-widest">
                <HistoryIcon class="w-4 h-4 mr-2.5 text-brand-500" /> Journal d'activités
              </h3>
              <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-200/50">Flux Financier</span>
            </div>
            
            <div v-if="!selectedClientDossier.timeline || selectedClientDossier.timeline.length === 0" class="p-12 text-center bg-white border border-dashed border-slate-200 rounded-[2.5rem] text-slate-400">
               <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                 <HistoryIcon class="w-8 h-8 opacity-20" />
               </div>
               <p class="font-black uppercase tracking-widest text-xs">Aucune opération</p>
            </div>

            <div v-else class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
               <div class="overflow-x-auto">
                 <table class="w-full text-left border-collapse">
                   <thead>
                     <tr class="bg-slate-50/50 border-b border-slate-100">
                       <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Temporel</th>
                       <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Désignation / Réf</th>
                       <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Débit (+)</th>
                       <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Crédit (-)</th>
                     </tr>
                   </thead>
                   <tbody class="divide-y divide-slate-50">
                     <tr v-for="entry in selectedClientDossier.timeline" :key="entry.id" class="hover:bg-slate-50/80 transition-colors group">
                       <td class="px-6 py-5 whitespace-nowrap">
                         <p class="text-xs font-black text-slate-700">{{ formatDate(entry.date) }}</p>
                         <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ new Date(entry.date).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'}) }}</p>
                       </td>
                       <td class="px-6 py-5">
                         <div class="flex items-center gap-3">
                           <div :class="entry.impact === 'increase' ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'" 
                                 class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 border shadow-sm">
                             <TrendingUpIcon v-if="entry.impact === 'increase'" class="w-4 h-4" />
                             <MinusIcon v-else class="w-4 h-4" />
                           </div>
                           <div>
                             <p class="text-xs font-black text-slate-900 leading-tight">{{ entry.type }}</p>
                             <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ entry.reference }}</p>
                             <p v-if="entry.description" class="text-[10px] text-slate-400 italic mt-1 line-clamp-1 group-hover:line-clamp-none transition-all">{{ entry.description }}</p>
                           </div>
                         </div>
                       </td>
                       <td class="px-6 py-5 text-right">
                         <span v-if="entry.impact === 'increase'" class="text-sm font-black text-slate-900">+{{ safeNumber(entry.amount) }}</span>
                         <span v-else class="text-xs font-bold text-slate-200">—</span>
                       </td>
                       <td class="px-6 py-5 text-right">
                         <span v-if="entry.impact === 'decrease'" class="text-sm font-black text-emerald-600">-{{ safeNumber(entry.amount) }}</span>
                         <span v-else class="text-xs font-bold text-slate-200">—</span>
                       </td>
                     </tr>
                   </tbody>
                 </table>
               </div>
            </div>
            
            <div class="relative overflow-hidden bg-brand-600 p-6 rounded-[2rem] border border-brand-700 shadow-xl shadow-brand-100 flex justify-between items-center group">
              <div class="absolute inset-0 bg-gradient-to-r from-brand-600 to-indigo-600 opacity-90"></div>
              <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-500"></div>
              
              <div class="relative shrink-0">
                <p class="text-[10px] font-black text-brand-100 uppercase tracking-[0.2em] mb-1">Encours Client</p>
                <span class="text-xs font-black text-white/60 uppercase tracking-widest">Solde Global</span>
              </div>
              <div class="relative text-right">
                <span class="text-2xl font-black text-white tracking-tight">
                  {{ safeNumber(selectedClientDossier.client.total_credit) }} <span class="text-xs text-brand-100 opacity-80">DH</span>
                </span>
                <p class="text-[9px] font-black text-brand-200 uppercase tracking-widest mt-1">Total restant à percevoir</p>
              </div>
            </div>
          </div>


          <!-- TAB: Devis -->
          <div v-if="activeDossierTab === 'devis'" class="animate-in fade-in duration-300">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-sm font-black text-slate-800 flex items-center uppercase tracking-widest">
                <FileTextIcon class="w-4 h-4 mr-2.5 text-amber-500" /> Documents & Devis
              </h3>
              <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-amber-100">Devis & Factures A4</span>
            </div>

            <div v-if="!selectedClientDossier.invoices || selectedClientDossier.invoices.length === 0" class="p-12 text-center bg-white border border-dashed border-slate-200 rounded-[2.5rem] text-slate-400">
               <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                 <FileTextIcon class="w-8 h-8 opacity-20" />
               </div>
               <p class="font-black uppercase tracking-widest text-xs">Aucun document</p>
            </div>

            <div v-else class="space-y-4">
              <div v-for="inv in selectedClientDossier.invoices" :key="inv.id" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between group hover:border-brand-200 transition-all">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm transform group-hover:rotate-6 transition-transform" :class="inv.type === 'invoice' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-amber-50 text-amber-600 border border-amber-100'">
                    <FileTextIcon class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="flex items-center gap-2 mb-0.5">
                      <span class="font-black text-sm text-slate-900">{{ inv.invoice_number }}</span>
                      <span :class="invoiceStatusClasses(inv.status)" class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase border tracking-widest">{{ invoiceStatusLabel(inv.status) }}</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ formatDate(inv.issue_date) }}
                      <span v-if="inv.type === 'quote' && inv.expiry_date" :class="inv.status === 'expired' ? 'text-rose-500' : ''"> • expire {{ formatDate(inv.expiry_date) }}</span>
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-8">
                  <div class="text-right hidden sm:block">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total TTC</p>
                    <p class="font-black text-slate-800 text-sm tracking-tight">
                      {{ safeNumber(inv.total) }} <span class="text-[10px] text-slate-400">DH</span>
                    </p>
                  </div>
                  <div class="flex flex-col items-end gap-2">
                    <button v-if="inv.type === 'invoice' && (Number(inv.total) - Number(inv.amount_paid)) > 0.01" 
                            @click="openInvoicePaymentModal(inv)" 
                            class="h-8 px-4 bg-emerald-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-100 flex items-center gap-2">
                      <CreditCardIcon class="w-3 h-3" /> Encaisser
                    </button>
                    <div v-else class="text-right">
                       <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.1em]">Solde</p>
                       <span class="text-[10px] font-black text-emerald-600 uppercase">Payé ✓</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: Atelier -->
          <div v-if="activeDossierTab === 'atelier'" class="animate-in fade-in duration-300">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-sm font-black text-slate-800 flex items-center uppercase tracking-widest">
                <LayoutDashboardIcon class="w-4 h-4 mr-2.5 text-blue-500" /> Suivi Atelier
              </h3>
              <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-blue-100">Production en cours</span>
            </div>

            <div v-if="!selectedClientDossier.workshop_jobs || selectedClientDossier.workshop_jobs.length === 0" class="p-12 text-center bg-white border border-dashed border-slate-200 rounded-[2.5rem] text-slate-400">
               <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                 <RotateCwIcon class="w-8 h-8 opacity-20" />
               </div>
               <p class="font-black uppercase tracking-widest text-xs">Aucune tâche</p>
            </div>

            <div v-else class="space-y-4">
              <div v-for="job in selectedClientDossier.workshop_jobs" :key="job.id" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm group">
                <div class="flex items-center justify-between mb-5 pb-5 border-b border-slate-50">
                  <div class="flex items-center gap-3">
                    <span class="w-12 h-8 bg-slate-900 text-white font-black text-xs flex items-center justify-center rounded-xl shadow-lg shadow-slate-900/10">#{{ job.queue_number }}</span>
                    <div>
                      <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date de dépôt</p>
                      <p class="text-xs font-black text-slate-700 tracking-tight">{{ formatDate(job.created_at) }}</p>
                    </div>
                  </div>
                  <span :class="workshopStatusClasses(job.status)" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase border tracking-widest">{{ workshopStatusLabel(job.status) }}</span>
                </div>
                <div class="flex flex-wrap gap-2.5">
                  <div v-for="s in job.services" :key="s.name" 
                       class="px-3 py-1.5 text-[9px] font-black rounded-xl uppercase tracking-widest transition-all" 
                       :class="s.is_done ? 'bg-emerald-50 text-emerald-600 border border-emerald-100/50' : 'bg-slate-50 text-slate-400 border border-slate-100'">
                    <div class="flex items-center gap-2">
                       <CheckCircleIcon v-if="s.is_done" class="w-3 h-3" />
                       <div v-else class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                       {{ s.name }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </transition>
    
      <transition name="fade">
        <div v-if="selectedClientDossier" @click="selectedClientDossier = null" class="fixed inset-0 z-[90] bg-slate-900/20 backdrop-blur-sm"></div>
      </transition>
    </Teleport>
    
    <!-- Custom Delete Modal -->
    <transition name="fade-slide">
      <div v-if="clientToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-100">
          <div class="flex items-center text-red-600 mb-6">
            <AlertCircleIcon class="w-10 h-10 mr-3" />
            <h3 class="text-2xl font-black">Confirmation</h3>
          </div>
          <p class="text-slate-600 font-medium text-lg mb-8">
            Êtes-vous sûr de vouloir supprimer le profil de <span class="font-bold text-slate-900">{{ clientToDelete.name }}</span> ? Cette action est irréversible.
          </p>
          
          <div v-if="deleteError" class="p-4 bg-red-50 text-red-600 rounded-xl font-bold mb-6 flex items-center">
             <AlertCircleIcon class="w-5 h-5 mr-2" /> {{ deleteError }}
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button @click="cancelDelete" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-2xl font-bold hover:bg-slate-200 transition-all">
              ANNULER
            </button>
            <button @click="confirmDelete" class="bg-red-500 text-white px-8 py-3 rounded-2xl font-black shadow-lg shadow-red-200 hover:bg-red-600 transition-all flex items-center">
              <Trash2Icon class="w-5 h-5 mr-2" /> OUI, SUPPRIMER
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Payment Modal -->
    <transition name="fade-slide">
      <div v-if="orderToPay" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl border border-slate-100">
          <h3 class="text-xl font-black mb-2 text-slate-800">Ajouter un Paiement</h3>
          <p class="text-sm text-slate-500 font-bold mb-6">Facture #{{ orderToPay.id }}</p>
          
          <div class="mb-6 p-4 bg-slate-50 rounded-2xl">
            <div class="flex justify-between mb-1">
               <span class="text-xs font-bold text-slate-400 uppercase">Reste à payer</span>
               <span class="font-black text-red-500">{{ (Number(orderToPay.net_total ?? orderToPay.total_sell_price) - Number(orderToPay.amount_paid)).toFixed(2) }} DH</span>
            </div>
          </div>

          <div class="space-y-2 mb-6">
            <label class="text-xs font-bold text-slate-500 uppercase">Montant Reçu (DH)</label>
            <input v-model="paymentAmount" type="number" step="0.01" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-emerald-500 transition-all font-black text-xl text-slate-900" placeholder="0.00">
          </div>

          <div class="flex justify-end gap-3">
            <button @click="orderToPay = null" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl font-bold hover:bg-slate-200 transition-all">
              Annuler
            </button>
            <button @click="submitPayment" class="bg-emerald-500 text-white px-6 py-2 rounded-xl font-black shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition-all">
              Encaisser
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Global Payment Modal -->
    <transition name="fade-slide">
      <div v-if="showGlobalPayment" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl border border-slate-100">
          <h3 class="text-xl font-black mb-2 text-slate-800">Régler la Dette Globale</h3>
          <p class="text-sm text-slate-500 font-bold mb-6">Client: {{ selectedClientDossier.client.name }}</p>
          
          <div class="mb-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <div class="flex justify-between mb-1">
               <span class="text-xs font-bold text-slate-400 uppercase">Dette totale</span>
               <span class="font-black text-red-600">{{ selectedClientDossier.client.total_credit }} DH</span>
            </div>
          </div>

          <div class="space-y-2 mb-6">
            <label class="text-xs font-bold text-slate-500 uppercase">Montant Reçu (DH)</label>
            <input v-model="globalPaymentAmount" type="number" step="0.01" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-emerald-500 transition-all font-black text-xl text-slate-900" placeholder="0.00">
          </div>

          <div class="flex justify-end gap-3">
            <button @click="showGlobalPayment = false" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl font-bold hover:bg-slate-200 transition-all">
              Annuler
            </button>
            <button @click="submitGlobalPayment" class="bg-emerald-500 text-white px-6 py-2 rounded-xl font-black shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition-all">
              Confirmer le Paiement
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Invoice Payment Modal -->
    <transition name="fade">
      <div v-if="invoiceToPay" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xl">
        <div class="bg-white/95 backdrop-blur-3xl rounded-[2.5rem] p-8 max-w-sm w-full shadow-2xl border border-white/20">
          <h3 class="text-xl font-black mb-2 text-slate-800 tracking-tight">Encaisser Facture</h3>
          <p class="text-sm text-slate-500 font-bold mb-6">N° {{ invoiceToPay.invoice_number }}</p>
          
          <div class="mb-6 p-4 bg-slate-50/80 rounded-2xl border border-slate-100">
            <div class="flex justify-between mb-1">
               <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Reste à payer</span>
               <span class="font-black text-red-600 text-lg tracking-tight">{{ (Number(invoiceToPay.total) - Number(invoiceToPay.amount_paid)).toFixed(2) }} DH</span>
            </div>
          </div>

          <div class="space-y-2 mb-6">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Montant Reçu (DH)</label>
            <input v-model="invoicePaymentAmount" type="number" step="0.01" class="w-full bg-slate-50/50 border border-slate-200 rounded-2xl p-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-black text-2xl text-slate-900" placeholder="0.00">
          </div>

          <div class="flex justify-end gap-3 mt-8">
            <button @click="invoiceToPay = null" class="bg-white border border-slate-200 text-slate-500 px-6 py-3 rounded-2xl font-black hover:bg-slate-50 hover:text-slate-700 transition-all text-xs uppercase tracking-widest">
              Annuler
            </button>
            <button @click="submitInvoicePayment" class="bg-emerald-500 text-white px-8 py-3 rounded-2xl font-black shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition-all text-xs uppercase tracking-widest active:scale-95">
              Confirmer
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Add Form -->
    <transition name="fade-slide">
      <div v-if="showAddForm" class="relative z-10 bg-white/80 backdrop-blur-2xl p-8 rounded-[2.5rem] border border-white/40 shadow-xl mb-8">
        <h3 class="text-2xl font-black mb-8 text-slate-900 tracking-tight">{{ form.id ? 'Modifier' : 'Ajouter' }} un Client</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Nom Complet / Société *</label>
            <input v-model="form.name" type="text" class="w-full bg-white/50 border border-slate-200/60 rounded-2xl p-4 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 transition-all font-bold text-sm">
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Numéro de Téléphone</label>
            <input v-model="form.phone" type="text" class="w-full bg-white/50 border border-slate-200/60 rounded-2xl p-4 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 transition-all font-bold text-sm">
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Adresse</label>
            <input v-model="form.address" type="text" class="w-full bg-white/50 border border-slate-200/60 rounded-2xl p-4 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 transition-all font-bold text-sm" placeholder="Rue, Quartier...">
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Ville</label>
            <input v-model="form.city" type="text" class="w-full bg-white/50 border border-slate-200/60 rounded-2xl p-4 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 transition-all font-bold text-sm" placeholder="Ville...">
          </div>
          <div class="md:col-span-2 space-y-2">
            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Notes internes</label>
            <textarea v-model="form.notes" rows="2" class="w-full bg-white/50 border border-slate-200/60 rounded-2xl p-4 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 transition-all font-bold text-sm resize-none" placeholder="VIP, remarques, préférences..."></textarea>
          </div>
        </div>
        <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-slate-100/50">
          <button @click="showAddForm = false" class="bg-white border border-slate-200 text-slate-500 px-8 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 transition-all">
            ANNULER
          </button>
          <button @click="saveClient" class="bg-brand-600 text-white px-8 py-3.5 rounded-2xl font-black shadow-lg shadow-brand-200 hover:bg-brand-700 transition-all text-xs uppercase tracking-widest flex items-center active:scale-95">
            {{ form.id ? 'ENREGISTRER' : 'CRÉER LE CLIENT' }}
          </button>
        </div>
      </div>
    </transition>

    <!-- HERO KPI / LOADING -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
      <SkeletonLoader v-for="i in 8" :key="i" type="card" />
    </div>

    <!-- Client Grid -->
    <div v-else class="relative z-10 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="cl in (filteredClients || [])" :key="cl?.id || Math.random()" 
           class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 hover:border-brand-200 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between cursor-pointer"
           @click="openClientDetails(cl)">
        
        <!-- Premium Ambient Decor -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-brand-50 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-125 transition-transform duration-500 pointer-events-none">
           <UserIcon class="w-24 h-24 text-slate-900" />
        </div>

        <div class="flex items-center mb-5 relative z-10">
          <div class="w-16 h-16 bg-slate-50 rounded-[1.25rem] flex items-center justify-center border border-slate-100 group-hover:bg-brand-50 transition-colors shadow-sm relative overflow-hidden">
            <span class="text-2xl font-black text-brand-600 font-heading">{{ cl.name?.charAt(0) || '?' }}</span>
          </div>
          <div class="ml-4">
            <h3 class="text-xl font-black text-slate-900 tracking-tight leading-tight group-hover:text-brand-600 transition-colors">{{ cl.name }}</h3>
            <p class="text-sm font-bold text-slate-400 flex items-center mt-1">
              <PhoneIcon class="w-3.5 h-3.5 mr-1.5" /> {{ cl.phone || 'N/A' }}
            </p>
            <p v-if="cl.city" class="text-xs font-medium text-slate-400 flex items-center mt-0.5">
              <MapPinIcon class="w-3.5 h-3.5 mr-1.5" /> {{ cl.city }}
            </p>
          </div>
        </div>

        <!-- Notes preview -->
        <p v-if="cl.notes" class="text-xs text-slate-400 font-medium mb-3 line-clamp-2 italic">{{ cl.notes }}</p>

        <!-- Debt/Credit Status -->
        <div class="bg-slate-50 rounded-2xl p-4 mb-6">
           <div class="flex justify-between items-center text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
             <span>Engagement Financier</span>
             <span v-if="cl.total_credit > 0" class="text-red-500 flex items-center">
                <AlertCircleIcon class="w-3 h-3 mr-1" /> En Dette
             </span>
             <span v-else class="text-emerald-500 flex items-center">
                <CheckCircleIcon class="w-3 h-3 mr-1" /> À Jour
             </span>
           </div>
           <div class="flex items-baseline gap-1">
             <span class="text-2xl font-black" :class="cl.total_credit > 0 ? 'text-red-600' : 'text-slate-900'">{{ cl.total_credit }}</span>
             <span class="text-sm font-bold text-slate-400">DH</span>
           </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex gap-2 relative z-10">
          <button @click.stop.prevent="openClientDetails(cl)" class="flex-1 py-2 bg-brand-50 text-brand-600 font-bold rounded-xl text-sm hover:bg-brand-100 transition-colors flex justify-center items-center">
             <Edit2Icon class="w-4 h-4 mr-2" /> Dossier
          </button>
          
          <button v-if="currentUser?.role === 'admin'" @click.stop.prevent="deleteClient(cl)" class="px-4 py-2 bg-red-50 text-red-500 font-bold rounded-xl text-sm hover:bg-red-100 transition-all flex items-center" title="Supprimer">
             <Trash2Icon class="w-4 h-4" />
          </button>

          <a :href="'tel:' + cl.phone" class="px-4 py-2 bg-emerald-50 text-emerald-600 font-bold rounded-xl text-sm hover:bg-emerald-100 transition-colors flex items-center" title="Appeler">
             <PhoneCallIcon class="w-4 h-4" />
          </a>
        </div>
      </div>

      <!-- Empty State -->
      <EmptyState v-if="filteredClients.length === 0" 
                  class="col-span-full border-none"
                  :icon="UserSearchIcon"
                  :title="searchQuery ? 'Aucun résultat trouvé' : 'Aucun client répertorié'"
                  :message="searchQuery ? 'Vérifiez l\'orthographe ou essayez un autre nom.' : 'Commencez par ajouter votre premier client pour centraliser vos données.'"
                  :actionLabel="!searchQuery ? 'Ajouter un Client' : ''"
                  @action="openAddForm" />
    </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import EmptyState from '@/Components/EmptyState.vue';
const toast = useToast();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
import { 
  UsersIcon, UserPlusIcon, UserIcon, PhoneIcon, 
  PhoneCallIcon, AlertCircleIcon, CheckCircleIcon, UserSearchIcon,
  Edit2Icon, Trash2Icon, XIcon, FileTextIcon, CalendarIcon, CreditCardIcon,
  RotateCwIcon, FileDownIcon, EyeIcon, PrinterIcon, MapPinIcon, StickyNoteIcon,
  HistoryIcon, PlusIcon, MinusIcon, ActivityIcon, TrendingUpIcon
} from 'lucide-vue-next';

const safeNumber = (val) => {
    const num = parseFloat(val);
    return isNaN(num) ? '0.00' : num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const clients = ref([]);
// Removed window.authUser ref
const showAddForm = ref(false);
const searchQuery = ref('');
const isLoading = ref(false);
const form = ref({ id: null, name: '', phone: '', address: '', city: '', notes: '' });

const clientToDelete = ref(null);
const deleteError = ref('');

const selectedClientDossier = ref(null);
const orderDateFilter = ref('');
const activeDossierTab = ref('factures');

const orderToPay = ref(null);
const paymentAmount = ref('');

const showGlobalPayment = ref(false);
const globalPaymentAmount = ref('');

const expandedOrders = ref({});
const toggleOrderDetails = (orderId) => {
  expandedOrders.value[orderId] = !expandedOrders.value[orderId];
};

const invoiceToPay = ref(null);
const invoicePaymentAmount = ref('');

const openInvoicePaymentModal = (inv) => {
  invoiceToPay.value = inv;
  invoicePaymentAmount.value = (Number(inv.total) - Number(inv.amount_paid)).toFixed(2);
};

const submitInvoicePayment = async () => {
  if (!invoicePaymentAmount.value || invoicePaymentAmount.value <= 0) return;
  const reste = Number(invoiceToPay.value.total) - Number(invoiceToPay.value.amount_paid);
  if (invoicePaymentAmount.value > (reste + 0.01)) {
    toast.error('Montant invalide.');
    return;
  }
  try {
    await axios.post(`/api/admin/orders/${invoiceToPay.value.id}/pay`, { 
      amount: parseFloat(invoicePaymentAmount.value),
      source: 'invoice'
    });
    const clientId = selectedClientDossier.value.client.id;
    toast.success('Paiement encaissé !');
    invoiceToPay.value = null;
    await loadClients();
    await openClientDetails({ id: clientId });
  } catch (e) {
    toast.error("Erreur d'encaissement.");
  }
};

const dossierTabs = computed(() => {
  const d = selectedClientDossier.value;
  if (!d) return [];
  return [
    { label: 'Profil', value: 'profile' },
    { label: 'Factures', value: 'factures', count: d.stats?.order_count || 0 },
    { label: 'Relevé', value: 'ledger' },
    { label: 'Devis & Fact.', value: 'devis', count: (d.invoices || []).length },
    { label: 'Atelier', value: 'atelier', count: d.stats?.workshop_jobs_count || 0 },
  ];
});

const formatDate = (d) => d ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d)) : '';

const invoiceStatusLabel = (s) => ({ draft: 'Brouillon', sent: 'Envoyé', paid: 'Payée', partial: 'Partielle', cancelled: 'Annulée', accepted: 'Accepté', refused: 'Refusé', expired: 'Expiré' }[s] || s);
const invoiceStatusClasses = (s) => ({
  draft: 'bg-slate-50 text-slate-500 border-slate-200',
  sent: 'bg-blue-50 text-blue-600 border-blue-100',
  paid: 'bg-emerald-50 text-emerald-600 border-emerald-100',
  partial: 'bg-amber-50 text-amber-600 border-amber-100',
  cancelled: 'bg-slate-50 text-slate-400 border-slate-200',
  accepted: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  refused: 'bg-rose-50 text-rose-600 border-rose-100',
  expired: 'bg-red-50 text-red-600 border-red-200',
}[s] || 'bg-slate-50 text-slate-500 border-slate-200');

const workshopStatusLabel = (s) => ({ waiting: 'En attente', in_progress: 'En cours', done: 'Terminé', delivered: 'Livré' }[s] || s);
const workshopStatusClasses = (s) => ({
  waiting: 'bg-amber-50 text-amber-600 border-amber-200',
  in_progress: 'bg-blue-50 text-blue-600 border-blue-100',
  done: 'bg-emerald-50 text-emerald-600 border-emerald-100',
  delivered: 'bg-slate-50 text-slate-500 border-slate-200',
}[s] || 'bg-slate-50 text-slate-500 border-slate-200');

const getLineItemName = (line) => {
  if (line.label && line.label !== 'null' && line.label.trim() !== '') return line.label;
  if (line.item) {
    if (line.item.name) return line.item.name;
    if (line.item_type === 'App\\Models\\StockPanel') return `Panneau ${line.item.type || ''} ${line.item.size_x || ''}x${line.item.size_y || ''}`.trim();
    if (line.item_type === 'App\\Models\\StockCanto') return `Bandchant ${line.item.color_name || line.item.name || ''} [${line.item.finish_type || 'STD'}]`.trim();
    if (line.item_type === 'App\\Models\\Consumable') return `Article ${line.item.name || ''}`.trim();
    if (line.item_type === 'App\\Models\\Service') return `${line.item.name || 'Service'}`.trim();
  }
  if (line.item_type) return `${line.item_type.replace('App\\Models\\', '')} ${line.item_id ? '#' + line.item_id : ''}`.trim();
  return 'Article Générique';
};

const normalizeLineBaseName = (rawName) => {
  if (!rawName) return '';
  return rawName
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client')
    .replace(/^Fourniture:\s*/gi, '')
    .replace(/^Collage Chant:\s*/gi, '')
    .replace(/^\[(?:Collage(?:\s*\+\s*\w+)*)\]\s*/gi, '')
    .trim();
};

const isCantoSplitLine = (rawName) => /^(Fourniture:|Collage Chant:|\[Collage\])/i.test((rawName || '').trim());

const hasCollageComponent = (rawName) => /^(Collage Chant:|\[Collage\])/i.test((rawName || '').trim());

const groupOrderLines = (lines) => {
  if (!lines?.length) return [];
  const groups = {};

  lines.forEach((line, index) => {
    const rawName = getLineItemName(line);
    const baseName = normalizeLineBaseName(rawName) || `Article ${index + 1}`;
    const qty = Number(line.quantity);
    const key = isCantoSplitLine(rawName) ? `canto_${baseName}_${qty}` : `line_${line.id || index}`;

    if (!groups[key]) {
      groups[key] = {
        ...line,
        displayName: baseName,
        has_collage: hasCollageComponent(rawName),
        quantity: qty,
        quantity_returned: Number(line.quantity_returned || 0),
        unit_sell_price: Number(line.unit_sell_price),
        total_line_sell: Number(line.total_line_sell),
      };
    } else {
      groups[key].total_line_sell += Number(line.total_line_sell);
      groups[key].unit_sell_price = groups[key].total_line_sell / qty;
      groups[key].has_collage = groups[key].has_collage || hasCollageComponent(rawName);
    }
  });

  return Object.values(groups).map((line) => ({
    ...line,
    displayName: line.has_collage ? `[Collage] ${line.displayName}` : line.displayName,
  }));
};

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

const printOrderInvoice = (order) => {
  const clientName = selectedClientDossier.value?.client?.name || order.client?.name || 'Client';
  const refunded = Number(order.total_refunded_amount || 0);
  const gross = Number(order.total_sell_price);
  
  // Dispatch global print event
  window.dispatchEvent(new CustomEvent('global-print', {
    detail: {
      order: order,
      items: order.lines || [],
      total: Number(order.net_total ?? (gross - refunded)),
      grossTotal: gross,
      totalRefunded: refunded,
      amountPaid: Number(order.amount_paid) || 0,
      clientName: clientName
    }
  }));
};

const filteredOrders = computed(() => {
  if (!selectedClientDossier.value) return [];
  let orders = selectedClientDossier.value.orders;
  if (orderDateFilter.value) {
    orders = orders.filter(o => o.created_at.startsWith(orderDateFilter.value));
  }
  return orders;
});

const openClientDetails = async (cl) => {
  try {
    const res = await axios.get(`/api/admin/clients/${cl.id}/history`);
    selectedClientDossier.value = res.data;
    orderDateFilter.value = '';
    activeDossierTab.value = 'factures';
    form.value = { id: cl.id, name: cl.name, phone: cl.phone || '', address: cl.address || '', city: cl.city || '', notes: cl.notes || '' };
  } catch(e) {
    toast.error("Erreur lors du chargement de l'historique du client.");
  }
};

const openPaymentModal = (order) => {
  orderToPay.value = order;
  paymentAmount.value = (Number(order.net_total ?? order.total_sell_price) - Number(order.amount_paid)).toFixed(2);
};

const submitPayment = async () => {
  if (!paymentAmount.value || paymentAmount.value <= 0) return;
  const reste = Number(orderToPay.value.total_sell_price) - Number(orderToPay.value.amount_paid);
  if (paymentAmount.value > (reste + 0.01)) {
    toast.error('Le montant (' + paymentAmount.value + ' DH) est supérieur au reste à payer (' + reste.toFixed(2) + ' DH).');
    return;
  }
  try {
    await axios.post(`/api/admin/orders/${orderToPay.value.id}/pay`, { amount: parseFloat(paymentAmount.value) });
    toast.success('Paiement encaissé avec succès!');
    const clientIdToRefresh = orderToPay.value.client_id;
    orderToPay.value = null;
    paymentAmount.value = '';
    await loadClients();
    if (selectedClientDossier.value) await openClientDetails({ id: clientIdToRefresh });
  } catch(e) {
    toast.error("Erreur lors de l'encaissement.");
  }
};

const openGlobalPaymentModal = () => {
  showGlobalPayment.value = true;
  globalPaymentAmount.value = selectedClientDossier.value.client.total_credit;
};

const submitGlobalPayment = async () => {
  if (!globalPaymentAmount.value || globalPaymentAmount.value <= 0) return;
  const totalCredit = Number(selectedClientDossier.value.client.total_credit);
  if (globalPaymentAmount.value > (totalCredit + 0.01)) {
    toast.error('Le montant (' + globalPaymentAmount.value + ' DH) est supérieur à la dette totale (' + totalCredit.toFixed(2) + ' DH).');
    return;
  }
  try {
    await axios.post(`/api/admin/clients/${selectedClientDossier.value.client.id}/pay`, { amount: parseFloat(globalPaymentAmount.value) });
    toast.success('Paiement global encaissé avec succès!');
    const clientIdToRefresh = selectedClientDossier.value.client.id;
    showGlobalPayment.value = false;
    globalPaymentAmount.value = '';
    await loadClients();
    await openClientDetails({ id: clientIdToRefresh });
  } catch(e) {
    toast.error("Erreur lors de l'encaissement global.");
  }
};

const recalculateCredit = async () => {
  if (!selectedClientDossier.value) return;
  try {
    const res = await axios.post(`/api/admin/clients/${selectedClientDossier.value.client.id}/recalculate`);
    toast.success(res.data.message);
    await loadClients();
    await openClientDetails({ id: selectedClientDossier.value.client.id });
  } catch (error) {
    toast.error(error.response?.data?.error || 'Erreur lors du recalcul');
  }
};

const deleteClient = (cl) => {
  clientToDelete.value = cl;
  deleteError.value = '';
};

const cancelDelete = () => {
  clientToDelete.value = null;
  deleteError.value = '';
};

const confirmDelete = async () => {
  try {
    await axios.delete(`/api/admin/clients/${clientToDelete.value.id}`);
    clientToDelete.value = null;
    loadClients();
  } catch(e) {
    if(e.response?.data?.error) deleteError.value = e.response.data.error;
    else deleteError.value = "Erreur lors de la suppression. Ce client a peut-être des factures liées.";
  }
};

const filteredClients = computed(() => {
  const currentClients = Array.isArray(clients.value) ? clients.value : [];
  const allClients = currentClients.filter(c => c !== null && c !== undefined);
  if (!searchQuery.value) return allClients;
  const q = searchQuery.value.toLowerCase();
  return allClients.filter(c => 
    c.name?.toLowerCase().includes(q) || 
    c.phone?.includes(q) ||
    c.city?.toLowerCase().includes(q)
  );
});

const loadClients = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/clients');
    if (Array.isArray(res.data)) clients.value = res.data;
    else if (res.data?.data && Array.isArray(res.data.data)) clients.value = res.data.data;
    else if (res.data?.clients) clients.value = res.data.clients;
    else clients.value = [];
  } catch(e) { console.error(e); } finally { isLoading.value = false; }
};

const openAddForm = () => {
  if (!showAddForm.value) form.value = { id: null, name: '', phone: '', address: '', city: '', notes: '' };
  showAddForm.value = !showAddForm.value;
};

const saveClient = async () => {
  if (!form.value.name) {
    toast.warning('Le nom est obligatoire.');
    return;
  }
  try {
    const payload = { name: form.value.name, phone: form.value.phone, address: form.value.address, city: form.value.city, notes: form.value.notes };
    if (form.value.id) {
      await axios.put(`/api/admin/clients/${form.value.id}`, payload);
      if (selectedClientDossier.value && selectedClientDossier.value.client.id === form.value.id) {
         selectedClientDossier.value.client.name = form.value.name;
         selectedClientDossier.value.client.phone = form.value.phone;
         selectedClientDossier.value.client.address = form.value.address;
         selectedClientDossier.value.client.city = form.value.city;
         selectedClientDossier.value.client.notes = form.value.notes;
      }
      toast.success('Client mis à jour avec succès !');
    } else {
      await axios.post('/api/admin/clients', payload);
      toast.success('Client créé avec succès !');
    }
    showAddForm.value = false;
    loadClients();
    form.value = { id: null, name: '', phone: '', address: '', city: '', notes: '' };
  } catch(e) { 
    const msg = e.response?.data?.message || 'Erreur lors de la sauvegarde du client.';
    toast.error(msg);
  }
};

const exportData = async (type) => {
    try {
        const res = await axios.post(`/api/export/${type}`, {}, { responseType: 'blob' });
        let fileName = `${type}_export_${new Date().getTime()}.xlsx`;
        const disposition = res.headers['content-disposition'] || res.headers['Content-Disposition'];
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches?.[1]) fileName = matches[1].replace(/['"]/g, '');
        }
        if (!fileName.toLowerCase().endsWith('.xlsx')) fileName += '.xlsx';
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        setTimeout(() => { document.body.removeChild(link); window.URL.revokeObjectURL(url); }, 200);
    } catch (e) {
        toast.error('Erreur lors du téléchargement de l\'export');
    }
};

onMounted(() => loadClients());
</script>

<style scoped>
.shadow-soft {
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
}

.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-slide-enter-from, .fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.slide-right-enter-active, .slide-right-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-right-enter-from, .slide-right-leave-to {
  opacity: 0;
  transform: translateX(100%) !important;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
