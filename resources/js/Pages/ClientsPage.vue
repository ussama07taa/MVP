<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 font-sans">
    
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-6">
        <div class="w-16 h-16 bg-white rounded-3xl flex items-center justify-center shadow-sm border border-slate-100">
          <UsersIcon class="w-10 h-10 text-brand-600" />
        </div>
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-1">Répertoire Clients</h1>
          <p class="text-slate-500 font-medium text-sm">Gérez votre base client et suivez les crédits en cours.</p>
        </div>
      </div>
      <div class="flex items-center gap-4 w-full md:w-auto">
        <button @click="loadClients" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-3.5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-brand-600 transition-transform duration-500" />
        </button>

        <div class="relative flex-1 md:w-64">
          <UserSearchIcon class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" />
          <input v-model="searchQuery" type="text" placeholder="Rechercher un client..." 
            class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 shadow-sm transition-all text-sm font-medium">
        </div>
        <button @click="exportData('clients')" class="px-6 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 font-bold rounded-xl transition-all border border-emerald-200 flex items-center">
          <FileDownIcon class="w-5 h-5 mr-2" /> Exporter Excel
        </button>

        <button @click="openAddForm" class="bg-brand-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-brand-200 hover:bg-brand-700 transition-all flex items-center">
          <UserPlusIcon class="w-5 h-5 mr-2" /> {{ showAddForm ? 'Fermer' : 'Nouveau Client' }}
        </button>
      </div>
    </div>

    <!-- Client Dossier Slide-over (CRM View) -->
    <transition name="slide-right">
      <div v-if="selectedClientDossier" class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl mx-4 sm:mx-auto bg-slate-50 shadow-2xl flex flex-col border-l border-slate-200" style="transform: translateX(0);">
        <!-- Drawer Header -->
        <div class="bg-white px-8 py-6 border-b border-slate-100 flex justify-between items-center z-10">
          <div class="flex items-center gap-4">
             <div class="w-12 h-12 bg-brand-50 rounded-2xl flex items-center justify-center text-brand-600 font-black text-xl">
               {{ selectedClientDossier.client.name.charAt(0) }}
             </div>
             <div>
               <h2 class="text-2xl font-black text-slate-900">{{ selectedClientDossier.client.name }}</h2>
               <p class="text-slate-500 font-bold text-sm flex items-center gap-3">
                 <span class="flex items-center"><PhoneIcon class="w-3 h-3 mr-1" /> {{ selectedClientDossier.client.phone || 'N/A' }}</span>
                 <span v-if="selectedClientDossier.client.city" class="flex items-center"><MapPinIcon class="w-3 h-3 mr-1" /> {{ selectedClientDossier.client.city }}</span>
               </p>
             </div>
          </div>
          <button @click="selectedClientDossier = null" class="p-2 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-200 transition-colors">
            <XIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Drawer Content -->
        <div class="flex-1 overflow-y-auto p-8 space-y-8">
          
          <!-- Stats Cards (4 cards) -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
               <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">CA Total</p>
               <h3 class="text-lg font-black text-slate-900">{{ selectedClientDossier.stats?.total_revenue || 0 }} DH</h3>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
               <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Crédit / Dette</p>
               <h3 class="text-lg font-black" :class="selectedClientDossier.client.total_credit > 0 ? 'text-red-500' : 'text-emerald-500'">
                 {{ selectedClientDossier.client.total_credit }} DH
               </h3>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
               <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dernière visite</p>
               <h3 class="text-sm font-black text-slate-700">{{ selectedClientDossier.stats?.last_order_date ? formatDate(selectedClientDossier.stats.last_order_date) : '—' }}</h3>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
               <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Client depuis</p>
               <h3 class="text-sm font-black text-slate-700">{{ selectedClientDossier.stats?.member_since ? formatDate(selectedClientDossier.stats.member_since) : '—' }}</h3>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="flex gap-3 flex-wrap">
            <button v-if="selectedClientDossier.client.total_credit > 0" @click="openGlobalPaymentModal" class="py-2.5 px-5 bg-emerald-600 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all flex items-center">
              <CreditCardIcon class="w-3.5 h-3.5 mr-2" /> RÉGLER LA DETTE
            </button>
            <a v-if="selectedClientDossier.client.phone" :href="'tel:' + selectedClientDossier.client.phone" class="py-2.5 px-5 bg-blue-50 text-blue-600 rounded-xl font-bold text-xs hover:bg-blue-100 transition-all flex items-center border border-blue-100">
              <PhoneCallIcon class="w-3.5 h-3.5 mr-2" /> Appeler
            </a>
          </div>

          <!-- Tabs -->
          <div class="flex border-b border-slate-200 gap-1">
            <button v-for="tab in dossierTabs" :key="tab.value" @click="activeDossierTab = tab.value"
              :class="activeDossierTab === tab.value ? 'text-brand-600 border-brand-600 bg-brand-50/50' : 'text-slate-400 border-transparent hover:text-slate-600'"
              class="px-4 py-3 text-xs font-black uppercase tracking-widest border-b-2 transition-all rounded-t-xl">
              {{ tab.label }}
              <span v-if="tab.count !== undefined" class="ml-1 px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded-md text-[9px]">{{ tab.count }}</span>
            </button>
          </div>

          <!-- TAB: Profil -->
          <div v-if="activeDossierTab === 'profile'">
            <div v-if="currentUser?.role === 'admin'" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
              <h3 class="text-sm font-black text-slate-800 flex items-center">
                <Edit2Icon class="w-4 h-4 mr-2 text-brand-500" /> Modifier le Profil
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-400 uppercase">Nom</label>
                  <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 font-medium text-sm">
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-400 uppercase">Téléphone</label>
                  <input v-model="form.phone" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 font-medium text-sm">
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-400 uppercase">Adresse</label>
                  <input v-model="form.address" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 font-medium text-sm" placeholder="Rue, Quartier...">
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-400 uppercase">Ville</label>
                  <input v-model="form.city" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 font-medium text-sm" placeholder="Ville...">
                </div>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase">Notes internes</label>
                <textarea v-model="form.notes" rows="3" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 font-medium text-sm resize-none" placeholder="VIP, remarques, préférences..."></textarea>
              </div>
              <div class="flex justify-end">
                 <button @click="saveClient" class="bg-brand-600 text-white px-6 py-2 rounded-xl font-bold shadow-md hover:bg-brand-700 transition-all text-sm">
                   Enregistrer
                 </button>
              </div>
            </div>
            <!-- Info Display for non-admin -->
            <div v-else class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-3">
              <div v-if="selectedClientDossier.client.address" class="flex items-start gap-2">
                <MapPinIcon class="w-4 h-4 text-slate-400 mt-0.5" />
                <span class="text-sm font-medium text-slate-700">{{ selectedClientDossier.client.address }}<span v-if="selectedClientDossier.client.city">, {{ selectedClientDossier.client.city }}</span></span>
              </div>
              <div v-if="selectedClientDossier.client.notes" class="flex items-start gap-2">
                <StickyNoteIcon class="w-4 h-4 text-amber-500 mt-0.5" />
                <span class="text-sm font-medium text-slate-700">{{ selectedClientDossier.client.notes }}</span>
              </div>
            </div>
          </div>

          <!-- TAB: Factures (Orders from POS) -->
          <div v-if="activeDossierTab === 'factures'">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-black text-slate-800 flex items-center">
                <FileTextIcon class="w-4 h-4 mr-2 text-indigo-500" /> Historique des Factures
              </h3>
              <input v-model="orderDateFilter" type="date" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold text-slate-600 focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <div v-if="filteredOrders.length === 0" class="p-8 text-center bg-white border border-dashed border-slate-200 rounded-2xl text-slate-400">
               <p class="font-bold">Aucune facture trouvée.</p>
            </div>

            <div v-else class="space-y-3">
               <div v-for="order in filteredOrders" :key="order.id" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm transition-all hover:border-indigo-100">
                 <div class="flex justify-between items-center border-b border-slate-50 pb-3">
                   <div class="flex items-center text-slate-500 font-bold text-sm">
                     <CalendarIcon class="w-4 h-4 mr-2" /> {{ new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                   </div>
                   <span class="px-3 py-1 bg-slate-100 text-slate-600 font-black text-xs rounded-lg uppercase tracking-wider">
                     Facture #{{ order.id }}
                   </span>
                 </div>
                 
                 <div class="flex justify-between items-center pt-3">
                   <div>
                     <p class="text-xs font-bold text-slate-400 uppercase">Total</p>
                     <p class="font-black text-slate-800">{{ order.total_sell_price }} DH</p>
                   </div>
                   <div>
                     <p class="text-xs font-bold text-slate-400 uppercase">Payé</p>
                     <p class="font-black text-emerald-600">{{ order.amount_paid }} DH</p>
                   </div>
                    <div class="text-right">
                      <p class="text-xs font-bold text-slate-400 uppercase">{{ (Number(order.total_sell_price) - Number(order.amount_paid)) > 0 ? 'Reste' : 'Surplus' }}</p>
                      <p class="font-black" :class="(Number(order.total_sell_price) - Number(order.amount_paid)) > 0 ? 'text-red-500' : 'text-emerald-500'">
                        {{ Math.abs(Number(order.total_sell_price) - Number(order.amount_paid)).toFixed(2) }} DH
                      </p>
                    </div>
                 </div>

                  <div v-if="expandedOrders[order.id]" class="mt-3 pt-3 border-t border-slate-50 space-y-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Détails des articles</p>
                    <div class="bg-slate-50 rounded-2xl p-4 space-y-2.5">
                      <div v-for="line in order.lines" :key="line.id" class="flex justify-between items-center text-xs text-slate-600">
                        <div class="font-bold flex items-center gap-2">
                          <span class="px-1.5 py-0.5 bg-slate-200 text-slate-700 font-black rounded text-[10px]">x{{ Number(line.quantity) }}</span>
                          <span>{{ getLineItemName(line) }}</span>
                          <span class="text-[10px] text-slate-400 font-normal">(P.U: {{ Number(line.unit_sell_price).toFixed(2) }} DH)</span>
                        </div>
                        <span class="font-black text-slate-800">{{ Number(line.total_line_sell).toFixed(2) }} DH</span>
                      </div>
                    </div>
                  </div>

                  <div class="mt-3 pt-3 border-t border-slate-50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                      <button @click="toggleOrderDetails(order.id)" class="text-indigo-600 hover:text-indigo-700 font-black text-xs flex items-center gap-1.5 transition-colors">
                        <EyeIcon class="w-3.5 h-3.5" />
                        {{ expandedOrders[order.id] ? 'Masquer' : 'Détails' }}
                      </button>
                      <button @click="printOrderInvoice(order)" class="text-slate-500 hover:text-slate-700 font-black text-xs flex items-center gap-1.5 transition-colors">
                        <PrinterIcon class="w-3.5 h-3.5" /> Imprimer
                      </button>
                    </div>
                    <div v-if="(Number(order.total_sell_price) - Number(order.amount_paid)) > 0.01">
                       <button @click="openPaymentModal(order)" class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl font-bold text-xs shadow-sm hover:bg-emerald-100 transition-all flex items-center">
                          <CreditCardIcon class="w-3.5 h-3.5 mr-1" /> Encaisser
                       </button>
                    </div>
                  </div>
               </div>
            </div>
          </div>

          <!-- TAB: Devis -->
          <div v-if="activeDossierTab === 'devis'">
            <div v-if="!selectedClientDossier.invoices || selectedClientDossier.invoices.length === 0" class="p-8 text-center bg-white border border-dashed border-slate-200 rounded-2xl text-slate-400">
               <p class="font-bold">Aucun devis ou facture trouvé.</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="inv in selectedClientDossier.invoices" :key="inv.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="inv.type === 'invoice' ? 'bg-brand-50' : 'bg-amber-50'">
                    <FileTextIcon class="w-4 h-4" :class="inv.type === 'invoice' ? 'text-brand-600' : 'text-amber-600'" />
                  </div>
                  <div>
                    <span class="font-black text-sm text-slate-900">{{ inv.invoice_number }}</span>
                    <p class="text-[11px] font-bold text-slate-400">{{ formatDate(inv.issue_date) }}
                      <span v-if="inv.type === 'quote' && inv.expiry_date" :class="inv.status === 'expired' ? 'text-rose-500' : ''"> • expire {{ formatDate(inv.expiry_date) }}</span>
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <span :class="invoiceStatusClasses(inv.status)" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase border">{{ invoiceStatusLabel(inv.status) }}</span>
                  <span class="font-black text-slate-800 text-sm">{{ Number(inv.total).toFixed(2) }} DH</span>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: Atelier -->
          <div v-if="activeDossierTab === 'atelier'">
            <div v-if="!selectedClientDossier.workshop_jobs || selectedClientDossier.workshop_jobs.length === 0" class="p-8 text-center bg-white border border-dashed border-slate-200 rounded-2xl text-slate-400">
               <p class="font-bold">Aucun travail d'atelier trouvé.</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="job in selectedClientDossier.workshop_jobs" :key="job.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-slate-100 font-black text-xs text-slate-700 rounded-lg">{{ job.queue_number }}</span>
                    <span class="text-xs font-bold text-slate-400">{{ formatDate(job.created_at) }}</span>
                  </div>
                  <span :class="workshopStatusClasses(job.status)" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase border">{{ workshopStatusLabel(job.status) }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="s in job.services" :key="s.name" class="px-2 py-1 text-[10px] font-bold rounded-lg" :class="s.is_done ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-50 text-slate-500 border border-slate-200'">
                    {{ s.name }} {{ s.is_done ? '✓' : '' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </transition>
    
    <!-- Dimmer for Drawer -->
    <transition name="fade">
      <div v-if="selectedClientDossier" @click="selectedClientDossier = null" class="fixed inset-0 z-40 bg-slate-900/20 backdrop-blur-sm"></div>
    </transition>
    
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
               <span class="font-black text-red-500">{{ (Number(orderToPay.total_sell_price) - Number(orderToPay.amount_paid)).toFixed(2) }} DH</span>
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

    <!-- Add Form -->
    <transition name="fade-slide">
      <div v-if="showAddForm" class="bg-white p-8 rounded-3xl border border-slate-100 shadow-soft mb-8">
        <h3 class="text-xl font-black mb-6 text-slate-800">{{ form.id ? 'Modifier le Client' : 'Ajouter un Client' }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Nom Complet / Société *</label>
            <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 transition-all font-medium">
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Numéro de Téléphone</label>
            <input v-model="form.phone" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 transition-all font-medium">
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Adresse</label>
            <input v-model="form.address" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 transition-all font-medium" placeholder="Rue, Quartier...">
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Ville</label>
            <input v-model="form.city" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 transition-all font-medium" placeholder="Ville...">
          </div>
          <div class="md:col-span-2 space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Notes internes</label>
            <textarea v-model="form.notes" rows="2" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-brand-500 transition-all font-medium resize-none" placeholder="VIP, remarques, préférences..."></textarea>
          </div>
        </div>
        <div class="mt-8 flex justify-end gap-3">
          <button @click="showAddForm = false" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-2xl font-bold hover:bg-slate-200 transition-all">
            ANNULER
          </button>
          <button @click="saveClient" class="bg-brand-600 text-white px-4 md:px-10 py-3 rounded-2xl font-black shadow-lg shadow-brand-100 hover:bg-brand-700 transition-all">
            {{ form.id ? 'ENREGISTRER LES MODIFICATIONS' : 'CRÉER LE PROFIL CLIENT' }}
          </button>
        </div>
      </div>
    </transition>

    <!-- Clients Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="cl in (filteredClients || [])" :key="cl?.id || Math.random()" class="bg-white p-6 rounded-3xl border border-slate-100 shadow-soft hover:border-brand-200 transition-all group relative overflow-hidden flex flex-col justify-between">
        <!-- Visual Decor -->
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-110 transition-transform pointer-events-none">
           <UserIcon class="w-20 h-20 text-slate-900" />
        </div>

        <div class="flex items-center mb-4">
          <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 group-hover:bg-brand-50 transition-colors">
            <span class="text-xl font-black text-brand-600">{{ cl.name?.charAt(0) || '?' }}</span>
          </div>
          <div class="ml-4">
            <h3 class="text-xl font-black text-slate-900 leading-tight">{{ cl.name }}</h3>
            <p class="text-sm font-bold text-slate-400 flex items-center mt-1">
              <PhoneIcon class="w-3 h-3 mr-1" /> {{ cl.phone || 'N/A' }}
            </p>
            <p v-if="cl.city" class="text-xs font-medium text-slate-400 flex items-center mt-0.5">
              <MapPinIcon class="w-3 h-3 mr-1" /> {{ cl.city }}
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
      <div v-if="filteredClients.length === 0" class="col-span-full py-20 bg-white rounded-3xl border border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-400">
        <UserSearchIcon class="w-20 h-20 mb-4 opacity-10" />
        <p class="text-xl font-bold">{{ searchQuery ? 'Aucun résultat trouvé' : 'Aucun client répertorié' }}</p>
        <p class="text-sm" v-if="!searchQuery">Commencez par ajouter votre premier client.</p>
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
  UsersIcon, UserPlusIcon, UserIcon, PhoneIcon, 
  PhoneCallIcon, AlertCircleIcon, CheckCircleIcon, UserSearchIcon,
  Edit2Icon, Trash2Icon, XIcon, FileTextIcon, CalendarIcon, CreditCardIcon,
  RotateCwIcon, FileDownIcon, EyeIcon, PrinterIcon, MapPinIcon, StickyNoteIcon
} from 'lucide-vue-next';

const clients = ref([]);
const currentUser = ref(window.authUser || {});
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

const dossierTabs = computed(() => {
  const d = selectedClientDossier.value;
  if (!d) return [];
  return [
    { label: 'Profil', value: 'profile' },
    { label: 'Factures', value: 'factures', count: d.stats?.order_count || 0 },
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

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

const printOrderInvoice = (order) => {
  const clientName = selectedClientDossier.value?.client?.name || order.client?.name || 'Client';
  const dateStr = new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  const linesHtml = (order.lines || []).map(line => {
    const itemName = getLineItemName(line);
    return `<tr><td>${formatItemName(itemName)}</td><td class="text-right">${Number(line.quantity).toFixed(2)}</td><td class="text-right">${Number(line.unit_sell_price).toFixed(2)} DH</td><td class="text-right" style="color:#0f172a;font-weight:800">${Number(line.total_line_sell).toFixed(2)} DH</td></tr>`;
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
  w.document.write('<html><head><title>Facture #' + order.id + '</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet"><style>body{font-family:\'Inter\',sans-serif;color:#1e293b;padding:40px;max-width:800px;margin:0 auto}h1{font-size:28px;font-weight:800;margin:0}.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #e2e8f0;padding-bottom:20px;margin-bottom:30px}.inv-num{font-size:14px;font-weight:800;color:#0f172a}.client-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:30px}table{width:100%;border-collapse:collapse;margin-bottom:30px}th{text-align:left;padding:12px 16px;font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:2px solid #e2e8f0}td{padding:16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:600;color:#475569}.text-right{text-align:right}.totals{width:320px;margin-left:auto;background:#f8fafc;border-radius:12px;padding:20px;border:1px solid #e2e8f0}.total-line{display:flex;justify-content:space-between;margin-bottom:12px;font-size:14px;font-weight:600;color:#64748b}.total-final{display:flex;justify-content:space-between;margin-top:15px;padding-top:15px;border-top:2px dashed #cbd5e1;font-size:20px;font-weight:800;color:#0f172a}.footer{margin-top:50px;text-align:center;font-size:12px;color:#94a3b8;font-weight:600;padding-top:20px;border-top:1px solid #f1f5f9}@media print{body{padding:0}@page{margin:1cm}}</style></head><body><div class="header"><div style="display:flex;align-items:center;gap:20px">' + logoHtml + '<div><h1>' + companyName + '</h1>' + rcIceHtml + '</div></div><div style="text-align:right"><h2 style="font-size:28px;font-weight:800;text-transform:uppercase;color:#cbd5e1;margin:0">Facture</h2><p class="inv-num">N° FACT-' + order.id + '</p><p style="font-size:12px;font-weight:600;color:#64748b;margin-top:5px">' + dateStr + '</p></div></div><div class="client-box"><p style="font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;margin:0 0 8px">Client</p><p style="font-size:16px;font-weight:800;margin:0">' + clientName + '</p></div><table><thead><tr><th>Désignation</th><th class="text-right">Qté</th><th class="text-right">Prix U.</th><th class="text-right">Total</th></tr></thead><tbody>' + linesHtml + '</tbody></table><div class="totals"><div class="total-line"><span>Sous-total</span><span>' + total.toFixed(2) + ' DH</span></div><div class="total-final"><span>Total TTC</span><span>' + total.toFixed(2) + ' DH</span></div>' + (paid > 0 ? '<div class="total-line" style="margin-top:15px"><span>Payé</span><span style="color:#10b981;font-weight:800">' + paid.toFixed(2) + ' DH</span></div>' : '') + (reste > 0 ? '<div class="total-line"><span style="color:#ef4444;font-weight:800">Reste à payer</span><span style="color:#ef4444;font-weight:800">' + reste.toFixed(2) + ' DH</span></div>' : '') + '</div><div class="footer">' + (companyPhone ? '<p>' + companyPhone + '</p>' : '') + '<p>' + footerText + '</p></div></body></html>');
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); }, 500);
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
    const res = await axios.get(`/api/clients/${cl.id}/history`);
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
  paymentAmount.value = (Number(order.total_sell_price) - Number(order.amount_paid)).toFixed(2);
};

const submitPayment = async () => {
  if (!paymentAmount.value || paymentAmount.value <= 0) return;
  const reste = Number(orderToPay.value.total_sell_price) - Number(orderToPay.value.amount_paid);
  if (paymentAmount.value > (reste + 0.01)) {
    toast.error('Le montant (' + paymentAmount.value + ' DH) est supérieur au reste à payer (' + reste.toFixed(2) + ' DH).');
    return;
  }
  try {
    await axios.post(`/api/orders/${orderToPay.value.id}/pay`, { amount: parseFloat(paymentAmount.value) });
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
    await axios.post(`/api/clients/${selectedClientDossier.value.client.id}/pay`, { amount: parseFloat(globalPaymentAmount.value) });
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
    await axios.delete(`/api/clients/${clientToDelete.value.id}`);
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
    const res = await axios.get('/api/clients');
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
      await axios.put(`/api/clients/${form.value.id}`, payload);
      if (selectedClientDossier.value && selectedClientDossier.value.client.id === form.value.id) {
         selectedClientDossier.value.client.name = form.value.name;
         selectedClientDossier.value.client.phone = form.value.phone;
         selectedClientDossier.value.client.address = form.value.address;
         selectedClientDossier.value.client.city = form.value.city;
         selectedClientDossier.value.client.notes = form.value.notes;
      }
      toast.success('Client mis à jour avec succès !');
    } else {
      await axios.post('/api/clients', payload);
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
