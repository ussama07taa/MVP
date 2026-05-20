<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 font-sans">
    
    <!-- Header & Search -->
    <div class="mb-12 flex flex-col lg:flex-row lg:items-end justify-between gap-8 animate-in fade-in slide-in-from-top duration-700">
      <div class="flex items-center space-x-6">
        <div class="relative group">
          <div class="absolute -inset-1 bg-gradient-to-tr from-amber-600 to-yellow-400 rounded-[32px] blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
          <div class="relative bg-white p-5 rounded-[28px] shadow-premium border border-slate-100">
             <LayersIcon class="w-10 h-10 text-amber-600" />
          </div>
        </div>
        <div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tight leading-none mb-3">Stock MDF & LATI</h1>
          <div class="flex items-center space-x-2">
            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <p class="text-slate-500 font-bold text-sm tracking-wide uppercase opacity-70">Gestion d'Inventaire Pro</p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Search & Refresh Group -->
        <div class="flex items-center bg-white/60 backdrop-blur-xl p-2 rounded-[24px] border border-white shadow-glass gap-2">
           <div class="relative group flex-1 min-w-[200px] md:min-w-[300px]">
              <SearchIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors" />
              <input v-model="searchQuery" type="text" placeholder="Rechercher..." 
                     class="w-full pl-10 pr-4 py-3 bg-slate-50/50 border-transparent rounded-2xl focus:ring-0 focus:bg-white transition-all font-bold text-slate-700 text-sm">
           </div>
           
           <button @click="loadPanels" 
             :class="isLoading ? 'opacity-50' : ''"
             class="p-3 bg-white text-slate-400 hover:text-brand-600 rounded-xl shadow-sm border border-slate-100 transition-all active:scale-90 group">
             <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 transition-transform duration-500" />
           </button>

           <div class="h-8 w-px bg-slate-200 mx-1"></div>

           <!-- View Switcher -->
           <div class="flex items-center p-1 bg-slate-100/50 rounded-xl">
             <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white text-brand-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="p-2 rounded-lg transition-all duration-200">
               <LayoutGridIcon class="w-4 h-4" />
             </button>
             <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white text-brand-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="p-2 rounded-lg transition-all duration-200 ml-1">
               <ListIcon class="w-4 h-4" />
             </button>
           </div>
        </div>

        <div class="flex items-center gap-2">
            <button @click="openAdjustmentModal" class="p-4 bg-white text-rose-500 hover:bg-rose-50 rounded-[22px] shadow-sm border border-slate-100 transition-all active:scale-95 group" title="Déclarer Casse">
              <i class="fa-solid fa-crack text-lg"></i>
            </button>
            
            <button @click="exportData('stock')" class="p-4 bg-white text-emerald-600 hover:bg-emerald-50 rounded-[22px] shadow-sm border border-slate-100 transition-all active:scale-95 group" title="Exporter Excel">
              <FileDownIcon class="w-5 h-5" />
            </button>

            <button @click="triggerImport" 
                    class="p-4 bg-white text-indigo-600 hover:bg-indigo-50 rounded-[22px] shadow-sm border border-slate-100 transition-all active:scale-95 group" 
                    title="Importer Stock Initial (Colonnes: code, nom, marque, quantite, prix_achat, prix_vente, longueur, largeur, epaisseur)">
              <FileUpIcon class="w-5 h-5" />
            </button>
            <input type="file" ref="importInput" @change="handleImportFile" class="hidden" accept=".xlsx,.xls,.csv">
        </div>

        <button @click="toggleAddForm" class="relative group overflow-hidden bg-slate-900 text-white px-8 py-4 rounded-[24px] font-black shadow-xl hover:shadow-2xl transition-all active:scale-95">
          <div class="absolute inset-0 bg-gradient-to-r from-brand-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          <span class="relative flex items-center">
            <PlusCircleIcon class="w-5 h-5 mr-2" /> {{ showAddForm ? 'Fermer' : 'Nouveau Stock' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Add/Edit Form -->
    <transition name="fade-slide">
      <div v-if="showAddForm" class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-soft mb-8 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-amber-500"></div>
        <h3 class="text-xl font-black mb-8 text-slate-800 flex items-center">
          <Edit3Icon v-if="editingId" class="w-5 h-5 mr-3 text-amber-500" />
          {{ editingId ? 'Modifier le Panneau' : 'Nouveau Stock de Panneaux' }}
        </h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Selection Article Existant -->
          <div class="lg:col-span-12 bg-amber-50/50 p-6 rounded-3xl border border-amber-100 mb-4">
             <label class="text-[10px] font-black text-amber-700 uppercase tracking-[0.2em] mb-3 block">Réceptionner un article déjà en stock ?</label>
             <select @change="handleExistingSelection($event)" class="w-full bg-white border-amber-200 rounded-2xl p-4 font-black text-slate-700 shadow-sm focus:ring-amber-500">
                <option value="">-- NOUVEAU PRODUIT (Céer une nouvelle fiche) --</option>
                 <option v-for="p in (panels || [])" :key="p?.id || Math.random()" :value="p?.id">
                    {{ p ? `#${p.id} - ${p.type} (${p.finish_type}) - ${p.color_code} - [Actuel: ${p.quantity}]` : '' }}
                 </option>
             </select>
             <p class="text-[9px] text-amber-600 font-bold mt-2 px-1 italic">Sélectionner un article fusionnera le nouveau stock et calculera le nouveau Prix de Revient Moyen (CUMP).</p>
          </div>

          <!-- Inputs -->
          <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2 md:col-span-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Type de Panneau (Nature)</label>
              <div class="flex gap-4">
                <button @click="form.type = 'MDF'" :class="form.type === 'MDF' ? 'bg-brand-600 text-white shadow-lg' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="flex-1 py-3 rounded-2xl font-black transition-all">MDF</button>
                <button @click="form.type = 'LATI'" :class="form.type === 'LATI' ? 'bg-brand-600 text-white shadow-lg' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="flex-1 py-3 rounded-2xl font-black transition-all">LATI</button>
                <button @click="form.type = 'CHUTE'" :class="form.type === 'CHUTE' ? 'bg-brand-600 text-white shadow-lg' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="flex-1 py-3 rounded-2xl font-black transition-all">CHUTE</button>
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Code Couleur</label>
              <input v-model="form.color_code" type="text" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nom de la Couleur (Designation)</label>
              <input v-model="form.color_name" type="text" placeholder="ex: CHENE TERRA" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Finition</label>
              <input v-model="form.finish_type" type="text" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Catalogue</label>
              <input v-model="form.provider_catalog" type="text" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Épaisseur</label><input v-model="form.thickness" type="number" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 font-black text-center"></div>
              <div class="space-y-2 col-span-2"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dimensions</label><div class="flex gap-2"><input v-model="form.size_x" type="number" class="w-1/2 bg-slate-50 rounded-2xl p-4 text-center font-black"><input v-model="form.size_y" type="number" class="w-1/2 bg-slate-50 rounded-2xl p-4 text-center font-black"></div></div>
            </div>
            <!-- Secure Edit Section (Premium UX) -->
            <div v-if="editingId" class="md:col-span-2 mt-4 pt-8 border-t border-slate-100 animate-in fade-in slide-in-from-bottom duration-500">
               <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-stretch">
                  
                  <!-- AUDIT DATA (Locked) -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/50 p-6 rounded-[32px] border border-slate-200/50 shadow-inner">
                     <div class="space-y-1.5">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center">
                           <ShieldCheckIcon class="w-3 h-3 mr-1.5 text-slate-400" /> Stock Physique
                        </label>
                        <div class="flex items-baseline gap-1.5">
                           <span class="text-3xl font-black text-slate-700 tracking-tighter">{{ form.quantity }}</span>
                           <span class="text-[10px] font-bold text-slate-400 uppercase">Plaques</span>
                        </div>
                        <p class="text-[8px] text-slate-400 font-bold italic leading-tight">Géré par les achats/retours</p>
                     </div>

                     <div class="space-y-1.5 sm:border-l sm:border-slate-200 sm:pl-6">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center">
                           <TrendingDownIcon class="w-3 h-3 mr-1.5 text-slate-400" /> Coût (CUMP)
                        </label>
                        <div class="flex items-baseline gap-1.5">
                           <span class="text-3xl font-black text-slate-700 tracking-tighter">{{ parseFloat(form.unit_cost).toFixed(2) }}</span>
                           <span class="text-[10px] font-bold text-slate-400 uppercase">DH</span>
                        </div>
                        <p class="text-[8px] text-slate-400 font-bold italic leading-tight">Valeur de stock moyenne</p>
                     </div>
                  </div>

                  <!-- COMMERCIAL DATA (Editable) -->
                  <div class="bg-brand-600 p-6 rounded-[32px] shadow-xl shadow-brand-200 relative overflow-hidden group/sell">
                     <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover/sell:bg-white/20 transition-all duration-500"></div>
                     <label class="text-[10px] font-black text-brand-100 uppercase tracking-[0.2em] flex items-center mb-4">
                        <TagIcon class="w-4 h-4 mr-2" /> Prix de Vente Public
                     </label>
                     
                     <div class="relative">
                        <input v-model="form.sell" type="number" step="1" 
                               class="w-full bg-white/10 border-white/20 rounded-2xl p-4 font-black text-white text-3xl focus:ring-4 focus:ring-white/20 focus:border-white/40 shadow-inner transition-all placeholder:text-white/30"
                               placeholder="0">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-brand-200 font-black">DH</span>
                     </div>
                     <p class="text-[9px] text-brand-200 font-bold px-1 italic mt-3 flex items-center">
                        <InfoIcon class="w-3 h-3 mr-1.5" /> Modifiable uniquement ici.
                     </p>
                  </div>

               </div>
            </div>

            <div v-else class="md:col-span-2 contents">
               <div class="space-y-2">
               <label class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Quantité</label>
               <div class="relative">
                  <input v-model="form.quantity" type="number" class="w-full bg-emerald-50 border-emerald-100 text-emerald-700 rounded-2xl p-4 font-black transition-all">
               </div>
               </div>
               <div class="space-y-2">
               <label class="text-[10px] font-black text-brand-600 uppercase tracking-widest">Prix Vente</label>
               <input v-model="form.sell" type="number" class="w-full bg-brand-50 border-brand-100 rounded-2xl p-4 font-black text-brand-700">
               </div>
               <!-- New Purchase Sync Inputs -->
               <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:col-span-2">
               <div class="space-y-2">
                  <label class="text-[10px] font-black text-rose-600 uppercase tracking-widest">Prix d'Achat Unitaire (DH)</label>
                  <input v-model="form.unit_cost" type="number" placeholder="Ex: 400" class="w-full bg-rose-50 border-rose-100 rounded-2xl p-4 font-black text-rose-700 focus:ring-2 focus:ring-rose-500">
                  <p class="text-[9px] text-slate-400 font-bold px-1 italic">Prix payé pour une seule plaque.</p>
               </div>
               <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Coût Global Total (DH)</label>
                  <input :value="(form.quantity * form.unit_cost).toFixed(2)" readonly type="number" class="w-full bg-slate-100 border-slate-200 rounded-2xl p-4 font-black text-slate-500 cursor-not-allowed shadow-inner">
                  <p class="text-[9px] text-slate-400 font-bold px-1 italic">Calculé automatiquement: {{ form.quantity }} x {{ form.unit_cost }} DH</p>
               </div>
               <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Avance Payée (DH)</label>
                  <input v-model="form.amount_paid" type="number" placeholder="Ex: 1000" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 font-black text-slate-700 focus:ring-2 focus:ring-brand-500">
                  <p class="text-[9px] text-slate-400 font-bold px-1 italic">Le reste sera ajouté à la dette du fournisseur.</p>
               </div>
               <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Photo de la Facture (Optionnel)</label>
                  <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="block w-full text-[10px] text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 border border-slate-100 rounded-2xl p-3 bg-white">
               </div>
               </div>
            </div>

            <!-- Supplier Selection -->
            <div class="space-y-2 md:col-span-2">
              <div class="flex justify-between items-center mb-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fournisseur</label>
                <button type="button" @click="showSupplierModal = true" class="text-[10px] font-black text-brand-600 hover:text-brand-800 flex items-center transition-colors">
                  <PlusCircleIcon class="w-3 h-3 mr-1" /> Nouveau Fournisseur
                </button>
              </div>
              <select v-model="form.supplier_id" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 font-black text-slate-700">
                <option :value="null">-- Sélectionner un fournisseur --</option>
                <option v-for="sup in suppliers" :key="sup?.id" :value="sup?.id">{{ sup?.name }}</option>
              </select>
            </div>
          </div>
          <!-- Palette -->
          <div class="lg:col-span-4 bg-slate-50 rounded-[32px] p-6 border border-slate-100 flex flex-col justify-center">
            <WoodTexturePicker v-model="form.color_code" @textureSelected="(t) => { form.color_name = t.name; if(!form.type) form.type = 'MDF'; }" />
          </div>
        </div>
        <div class="mt-8 flex justify-end gap-4">
          <button @click="saveMdf" class="bg-emerald-600 text-white px-12 py-4 rounded-2xl font-black shadow-lg hover:bg-emerald-700 transition-all uppercase tracking-widest text-xs flex items-center">
            <CheckCircleIcon class="w-5 h-5 mr-3" /> {{ editingId ? 'Mettre à jour' : 'Enregistrer le stock' }}
          </button>
        </div>
      </div>
    </transition>

    <!-- Content Area -->
    <div v-if="filteredPanels.length > 0">
      
      <!-- GRID VIEW -->
      <transition-group name="list" tag="div" v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-8">
        <div v-for="pnl in filteredPanels" :key="pnl?.id" class="bg-white p-7 rounded-[45px] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_20px_50px_rgb(0,0,0,0.08)] hover:border-brand-200 transition-all duration-500 group relative overflow-hidden flex flex-col h-full">
          
          <!-- Glossy Decor -->
          <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full opacity-[0.03] group-hover:opacity-[0.08] transition-all duration-700" :style="{ backgroundColor: getHexColor(pnl.color_code) }"></div>

          <!-- Card Header -->
          <div class="flex justify-between items-start mb-6">
            <div class="relative group/texture">
               <div class="w-24 h-24 rounded-[32px] flex items-center justify-center border-4 border-white relative overflow-hidden shadow-2xl transition-transform duration-500 group-hover:scale-105" :style="{ backgroundColor: getHexColor(pnl.color_code) }">
                  <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]"></div>
               </div>
               <!-- Low Stock Indicator -->
               <div v-if="pnl.quantity <= 2" class="absolute -top-2 -left-2 bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg animate-pulse border-2 border-white">
                  <AlertTriangleIcon class="w-4 h-4" />
               </div>
            </div>
            
            <div class="flex flex-col items-end">
               <span class="text-[9px] font-black bg-slate-50 text-slate-400 px-3 py-1.5 rounded-full uppercase tracking-widest border border-slate-100 mb-3">Ref: #{{ pnl?.id }}</span>
               <div class="flex gap-2 bg-white/50 backdrop-blur-sm p-1 rounded-2xl border border-slate-50">
                  <button @click="editMdf(pnl)" class="p-2.5 text-slate-400 hover:text-brand-600 hover:bg-white rounded-xl transition-all shadow-sm"><Edit3Icon class="w-4 h-4" /></button>
                  <button @click="confirmDeleteMdf(pnl?.id)" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-white rounded-xl transition-all shadow-sm"><Trash2Icon class="w-4 h-4" /></button>
               </div>
            </div>
          </div>
          
          <!-- Details -->
          <div class="mb-6">
            <h3 class="text-2xl font-black text-slate-900 mb-1 uppercase tracking-tight group-hover:text-brand-600 transition-colors">
              {{ pnl.color_name || pnl.type }} <span class="text-slate-400 font-medium not-italic ml-1 text-lg">{{ pnl.finish_type }}</span>
            </h3>
            <div class="flex items-center gap-2">
               <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[9px] font-black tracking-widest uppercase">{{ pnl.type }}</span>
               <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ pnl.provider_catalog || 'SANS MARQUE' }}</span>
               <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
               <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ pnl.color_code || 'N/A' }}</span>
            </div>
          </div>

          <!-- Meta Info -->
          <div class="grid grid-cols-2 gap-4 mb-6">
             <div class="bg-slate-50/50 p-4 rounded-3xl border border-slate-100 group-hover:bg-white group-hover:border-brand-50 transition-all">
                <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Format (mm)</span>
                <span class="text-xs font-black text-slate-800">{{ pnl.size_x }}×{{ pnl.size_y }}</span>
             </div>
             <div class="bg-slate-50/50 p-4 rounded-3xl border border-slate-100 group-hover:bg-white group-hover:border-brand-50 transition-all">
                <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Épaisseur</span>
                <span class="text-xs font-black text-slate-800">{{ pnl.thickness }}mm</span>
             </div>
          </div>

          <!-- Stock Progress -->
          <div class="space-y-3 mb-8">
            <div class="flex justify-between items-end px-1">
              <div>
                 <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Disponibilité</span>
                 <span class="text-[10px] font-black uppercase tracking-wider" :class="pnl.quantity <= 2 ? 'text-red-500' : 'text-emerald-500'">
                    {{ pnl.quantity <= 0 ? 'En rupture' : (pnl.quantity <= 2 ? 'Stock Critique' : 'En Stock') }}
                 </span>
              </div>
              <span class="text-lg font-black tracking-tight" :class="pnl.quantity <= 2 ? 'text-red-600' : 'text-slate-800'">{{ pnl.quantity }} <span class="text-[10px] text-slate-400 uppercase">U</span></span>
            </div>
            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden p-0.5">
              <div class="h-full rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(0,0,0,0.1)]" 
                   :class="pnl.quantity <= 2 ? 'bg-gradient-to-r from-rose-500 to-red-600' : 'bg-gradient-to-r from-brand-500 to-indigo-600'"
                   :style="{ width: (Math.min(pnl.quantity, 20) / 20 * 100) + '%' }"></div>
            </div>
          </div>

          <!-- Footer/Price -->
          <div class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between">
            <div>
               <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Prix Vente</span>
               <div class="text-3xl font-black text-slate-900 tracking-tighter leading-none">{{ pnl.base_price_sell }} <span class="text-sm font-bold text-slate-400">DH</span></div>
            </div>
            <div v-if="pnl.supplier" class="flex flex-col items-end">
               <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest mb-1">Source</span>
               <div class="flex items-center text-[10px] font-black text-slate-500 uppercase bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                  <TruckIcon class="w-3 h-3 mr-1.5 text-brand-500" />
                  {{ pnl.supplier.name.split(' ')[0] }}
               </div>
            </div>
          </div>
        </div>
      </transition-group>

      <!-- LIST VIEW -->
      <div v-else class="bg-white rounded-[40px] shadow-soft border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-100">
                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Produit</th>
                <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Dimensions</th>
                <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Stock</th>
                <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Prix Vente</th>
                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="pnl in filteredPanels" :key="pnl?.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <div class="w-12 h-12 rounded-2xl flex-shrink-0 border-2 border-white shadow-md relative overflow-hidden mr-4" :style="{ backgroundColor: getHexColor(pnl.color_code) }">
                       <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]"></div>
                    </div>
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[9px] font-black tracking-tighter">{{ pnl.type }}</span>
                        <div class="font-black text-slate-900 uppercase italic">{{ pnl.color_name || pnl.color_code }}</div>
                      </div>
                      <div class="text-[10px] font-bold text-slate-400 uppercase">{{ pnl.color_code }} <span class="mx-1">•</span> {{ pnl.provider_catalog || 'Générique' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5 font-black text-slate-600 text-sm">
                  {{ pnl.size_x }}×{{ pnl.size_y }} <span class="text-[10px] text-slate-300 mx-1">|</span> {{ pnl.thickness }}mm
                </td>
                <td class="px-6 py-5 text-center">
                   <span class="inline-flex items-center px-4 py-2 rounded-2xl text-xs font-black uppercase tracking-widest" :class="pnl.quantity <= 2 ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100'">
                      {{ pnl.quantity }} Plaques
                   </span>
                </td>
                <td class="px-6 py-5 text-right font-black text-brand-600 text-lg">
                  {{ pnl.base_price_sell }} <span class="text-[10px] text-brand-400 font-bold uppercase ml-0.5">DH</span>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex justify-end gap-2">
                    <button @click="editMdf(pnl)" class="p-2.5 text-slate-400 hover:text-brand-600 hover:bg-white rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"><Edit3Icon class="w-4 h-4" /></button>
                    <button @click="confirmDeleteMdf(pnl?.id)" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-white rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"><Trash2Icon class="w-4 h-4" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="relative group mt-12 py-32 bg-white/40 backdrop-blur-md rounded-[64px] border-2 border-dashed border-slate-200/50 flex flex-col items-center justify-center overflow-hidden animate-in fade-in zoom-in duration-700">
       <div class="absolute -inset-10 bg-gradient-to-tr from-brand-500/5 to-indigo-500/5 rounded-full blur-3xl group-hover:opacity-100 transition duration-1000"></div>
       <div class="relative bg-white/80 p-8 rounded-[32px] shadow-soft border border-slate-50 mb-6">
          <PackageSearchIcon class="w-20 h-20 text-slate-200 group-hover:text-brand-400 transition-colors duration-500" />
       </div>
       <div class="relative text-center max-w-sm px-6">
          <p class="font-black text-slate-900 uppercase tracking-widest text-lg mb-2">Inventaire Vide</p>
          <p class="text-slate-400 font-bold text-sm">Aucun panneau ne correspond à votre recherche. Essayez d'ajouter un nouvel article ou d'ajuster vos filtres.</p>
       </div>
       <button @click="toggleAddForm" class="mt-8 relative px-8 py-3 bg-white text-brand-600 rounded-2xl font-black text-sm border border-brand-100 shadow-sm hover:shadow-md hover:border-brand-200 transition-all active:scale-95">
         Commencer l'Inventaire
       </button>
    </div>

    <!-- DELETE MODAL -->
    <TransitionRoot as="template" :show="deleteModalOpen">
      <Dialog as="div" class="relative z-50" @close="deleteModalOpen = false">
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" />
        </TransitionChild>
        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-[32px] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg mx-4 sm:mx-auto">
                <div class="bg-white px-8 pt-10 pb-8 text-center">
                  <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-50 mb-6">
                    <AlertTriangleIcon class="h-10 w-10 text-red-600" aria-hidden="true" />
                  </div>
                  <DialogTitle as="h3" class="text-2xl font-black text-slate-900 uppercase">Supprimer ce panneau ?</DialogTitle>
                  <p class="mt-4 text-slate-500 font-bold">Cette action est irréversible. Le panneau sera retiré définitivement de l'inventaire.</p>
                </div>
                <div class="bg-slate-50 px-8 py-6 flex flex-col sm:flex-row-reverse gap-3">
                  <button type="button" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-8 py-4 text-sm font-black text-white shadow-lg hover:bg-red-700 sm:w-auto uppercase tracking-widest transition-all active:scale-95" @click="executeDelete">Confirmer la suppression</button>
                  <button type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl bg-white px-8 py-4 text-sm font-bold text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-100 sm:mt-0 sm:w-auto transition-all" @click="deleteModalOpen = false">Annuler</button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- ADJUSTMENT / KOSOR MODAL -->
    <div v-if="isAdjustModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-[40px] w-full max-w-md shadow-2xl overflow-hidden" @click.stop>
            <!-- Header -->
            <div class="bg-rose-500 p-8 flex justify-between items-start">
                <div>
                    <h3 class="text-2xl font-black text-white flex items-center">
                        <i class="fa-solid fa-triangle-exclamation mr-3 text-rose-200"></i> Déclarer une Casse
                    </h3>
                    <p class="text-sm text-rose-100 mt-1 font-medium">Retirer des articles endommagés du stock</p>
                </div>
                <button @click="closeAdjustmentModal" class="text-rose-200 hover:text-white transition-colors bg-white/10 p-2 rounded-xl">
                    <XIcon class="w-6 h-6" />
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-8 space-y-6">
                <!-- Select Article -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Article Endommagé *</label>
                    <select v-model="adjustForm.item_id" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 font-black text-slate-700 appearance-none shadow-inner">
                        <option value="">Sélectionner un panneau...</option>
                        <option v-for="item in (panels || [])" :key="item?.id || Math.random()" :value="item?.id">
                            {{ item ? `${item.type} - ${item.color_code} (Stock: ${item.quantity})` : '' }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Quantité -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Qté à retirer *</label>
                        <div class="relative group">
                            <input type="number" v-model="adjustForm.quantity" min="1" class="w-full p-4 bg-rose-50/50 border border-rose-100 text-rose-900 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 font-black text-xl shadow-inner transition-all">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 font-black text-rose-300 text-xs uppercase tracking-widest">Pcs</span>
                        </div>
                    </div>
                    
                    <!-- Motif -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Motif *</label>
                        <select v-model="adjustForm.reason" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 font-black text-slate-700 appearance-none shadow-inner">
                            <option value="kosor">Kosor / Cassé</option>
                            <option value="chute">Chute inexploitable</option>
                            <option value="erreur">Erreur d'inventaire</option>
                        </select>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Détails / Notes</label>
                    <textarea v-model="adjustForm.notes" rows="3" placeholder="Ex: Panneau rayé lors du transport..." class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 text-sm font-medium text-slate-600 shadow-inner transition-all"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 p-6 border-t border-slate-100 flex justify-end space-x-4">
                <button @click="closeAdjustmentModal" class="px-6 py-4 text-sm font-black text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all">
                    Annuler
                </button>
                <button @click="submitAdjustment" :disabled="!adjustForm.item_id || adjustForm.quantity <= 0" 
                        class="px-8 py-4 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black rounded-2xl transition-all shadow-xl shadow-rose-200 flex items-center active:scale-95">
                    <CheckIcon class="w-5 h-5 mr-2" /> Valider la Casse
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Add Supplier Modal -->
    <div v-if="showSupplierModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[60] flex items-center justify-center p-4">
      <div class="bg-white rounded-[32px] w-full max-w-md overflow-hidden shadow-2xl border border-slate-100 transform transition-all animate-fade-in-scale">
        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
          <h3 class="font-black text-xl text-slate-800 uppercase tracking-tight">Nouveau Fournisseur</h3>
          <button type="button" @click="showSupplierModal = false" class="w-10 h-10 rounded-full flex items-center justify-center text-slate-400 hover:bg-white hover:text-slate-600 transition-all">
            <Trash2Icon class="w-5 h-5 rotate-45" /> <!-- Using rotate-45 trash icon as an X -->
          </button>
        </div>
        <div class="p-8 space-y-6">
          <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Nom / Entreprise *</label>
            <input type="text" v-model="newSupplier.name" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black text-slate-700" placeholder="Ex: Atlas Bois">
          </div>
          <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Téléphone</label>
            <input type="text" v-model="newSupplier.phone" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black text-slate-700" placeholder="06...">
          </div>
        </div>
        <div class="p-6 border-t border-slate-50 bg-slate-50/50 flex justify-end gap-3">
          <button type="button" @click="showSupplierModal = false" class="px-6 py-3 font-bold text-slate-500 hover:text-slate-700 transition-colors uppercase text-xs tracking-widest">
            Annuler
          </button>
          <button type="button" @click="saveNewSupplier" :disabled="!newSupplier.name || isSavingSupplier" class="px-8 py-3 font-black text-white bg-brand-600 hover:bg-brand-700 rounded-2xl shadow-lg shadow-brand-200 transition-all active:scale-95 disabled:opacity-50 flex items-center uppercase text-xs tracking-widest">
            <div v-if="isSavingSupplier" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></div>
            <CheckCircleIcon v-else class="w-4 h-4 mr-2" />
            Enregistrer
          </button>
        </div>
      </div>
    </div>

    <!-- IMPORT MODAL (Master Workflow) -->
    <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md animate-fade-in">
        <div class="bg-white rounded-[48px] w-full max-w-xl shadow-2xl overflow-hidden" @click.stop>
            <!-- Header -->
            <div class="bg-gradient-to-br from-indigo-600 to-brand-600 p-10 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                <div class="relative flex justify-between items-start">
                    <div>
                        <h3 class="text-3xl font-black text-white flex items-center tracking-tight">
                           <FileUpIcon class="w-8 h-8 mr-3 text-indigo-200" /> Importer Stock Initial
                        </h3>
                        <p class="text-indigo-100 mt-2 font-medium">Initialisez votre inventaire en masse via Excel.</p>
                    </div>
                    <button @click="isImportModalOpen = false" class="text-indigo-200 hover:text-white transition-colors bg-white/10 p-2 rounded-2xl backdrop-blur-md">
                        <XIcon class="w-6 h-6" />
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="p-10 space-y-10">
                <!-- Step 1: Template -->
                <div class="flex gap-6 items-start group">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex flex-shrink-0 items-center justify-center text-indigo-600 font-black text-xl shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">1</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 mb-1">Télécharger le modèle</h4>
                        <p class="text-sm text-slate-500 font-medium mb-4">Utilisez notre structure standard pour éviter toute erreur de formatage.</p>
                        <button @click="downloadStockTemplate" class="inline-flex items-center px-5 py-2.5 bg-slate-50 text-indigo-600 border border-indigo-100 rounded-xl font-black text-xs hover:bg-indigo-50 transition-all active:scale-95 shadow-sm">
                           <FileDownIcon class="w-4 h-4 mr-2" /> Télécharger le Modèle (.xlsx)
                        </button>
                    </div>
                </div>

                <!-- Step 2: Fill -->
                <div class="flex gap-6 items-start group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex flex-shrink-0 items-center justify-center text-amber-600 font-black text-xl shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-all duration-300">2</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 mb-1">Préparer vos données</h4>
                        <p class="text-sm text-slate-500 font-medium leading-relaxed">Remplissez le fichier avec vos quantités actuelles. Respectez bien les colonnes <code class="bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600 font-bold">code</code> et <code class="bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600 font-bold">quantite</code>.</p>
                    </div>
                </div>

                <!-- Step 3: Upload -->
                <div class="flex gap-6 items-start group">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex flex-shrink-0 items-center justify-center text-emerald-600 font-black text-xl shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">3</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 mb-1">Importer le fichier</h4>
                        <p class="text-sm text-slate-500 font-medium mb-5">Une fois prêt, sélectionnez votre fichier pour injecter le stock.</p>
                        
                        <div class="relative group/input">
                           <input type="file" @change="handleImportFile" class="hidden" ref="importInputModal" accept=".xlsx,.xls,.csv">
                           <button @click="$refs.importInputModal.click()" :disabled="isLoading" 
                                   class="w-full flex flex-col items-center justify-center py-8 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50 hover:bg-white hover:border-brand-400 hover:shadow-premium transition-all group-hover/input:scale-[1.01]">
                               <div v-if="isLoading" class="flex flex-col items-center">
                                   <RotateCwIcon class="w-10 h-10 text-brand-500 animate-spin mb-2" />
                                   <span class="text-xs font-black text-brand-600 uppercase tracking-widest">Importation en cours...</span>
                               </div>
                               <div v-else class="flex flex-col items-center">
                                   <div class="w-14 h-14 bg-white rounded-2xl shadow-premium flex items-center justify-center mb-3 text-slate-400 group-hover:text-brand-500 transition-colors">
                                       <UploadCloudIcon class="w-8 h-8" />
                                   </div>
                                   <span class="text-sm font-black text-slate-800">Cliquez pour choisir un fichier</span>
                                   <span class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">Excel ou CSV supportés</span>
                               </div>
                           </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { 
  LayersIcon, RotateCwIcon, SearchIcon, PlusCircleIcon, 
  Edit3Icon, Trash2Icon, LayoutGridIcon, ListIcon, MoreHorizontalIcon,
  PackageIcon, HistoryIcon, DollarSignIcon, XIcon, CheckIcon,
  CheckCircleIcon, AlertTriangleIcon, TruckIcon, ShieldCheckIcon,
  TrendingDownIcon, TagIcon, InfoIcon, PackageSearchIcon, FileDownIcon, FileUpIcon,
  UploadCloudIcon
} from 'lucide-vue-next';
import WoodTexturePicker from '@/Components/WoodTexturePicker.vue';
import { commonTextures } from '@/colors';

// State
const panels = ref([]);
const suppliers = ref([]);
const showAddForm = ref(false);
const editingId = ref(null);
const searchQuery = ref('');
const viewMode = ref('grid'); // 'grid' or 'list'
const invoiceFile = ref(null);
const isLoading = ref(false);
const importInput = ref(null);
const importInputModal = ref(null);
const isImportModalOpen = ref(false);

// Quick Supplier Add State
const showSupplierModal = ref(false);
const isSavingSupplier = ref(false);
const newSupplier = ref({ name: '', phone: '' });

// Adjustment (Kosor) State
const isAdjustModalOpen = ref(false);
const adjustForm = ref({ item_id: '', quantity: 1, reason: 'kosor', notes: '' });

const handleFileUpload = (event) => {
  invoiceFile.value = event.target.files[0];
};

// Modal State
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

// Color Palette removed (moved to shared colors.js)

const form = ref({ 
  existing_id: null,
  type: 'MDF', finish_type: '', color_code: '', color_name: '', provider_catalog: '', 
  size_x: 2800, size_y: 2100, thickness: 18, quantity: 10, unit_cost: 400, sell: 550,
  supplier_id: null, amount_paid: 0
});

// Computed: Live Search
const filteredPanels = computed(() => {
  if (!searchQuery.value) return panels.value;
  const q = searchQuery.value.toLowerCase();
  return panels.value.filter(p => 
    (p.type && p.type.toLowerCase().includes(q)) || 
    (p.color_name && p.color_name.toLowerCase().includes(q)) ||
    (p.finish_type && p.finish_type.toLowerCase().includes(q)) ||
    (p.color_code && p.color_code.toLowerCase().includes(q)) ||
    (p.provider_catalog && p.provider_catalog.toLowerCase().includes(q)) ||
    (p.supplier && p.supplier.name.toLowerCase().includes(q))
  );
});

// Methods
const loadPanels = async () => {
  isLoading.value = true;
  try { 
    const res = await axios.get('/api/admin/panels'); 
    panels.value = res.data; 
  } catch(e) { 
    console.error(e); 
  } finally {
    isLoading.value = false;
  }
};

const loadSuppliers = async () => {
  try { const res = await axios.get('/api/admin/suppliers'); suppliers.value = res.data; } catch(e) { console.error(e); }
};

const saveNewSupplier = async () => {
  if (!newSupplier.value.name) return;
  isSavingSupplier.value = true;
  try {
    const res = await axios.post('/api/admin/suppliers', newSupplier.value);
    suppliers.value.push(res.data);
    form.value.supplier_id = res.data.id;
    showSupplierModal.value = false;
    newSupplier.value = { name: '', phone: '' };
  } catch (e) {
    alert('Erreur lors de la création du fournisseur');
  } finally {
    isSavingSupplier.value = false;
  }
};

const toggleAddForm = () => {
  if (showAddForm.value && editingId.value) { 
    editingId.value = null; resetForm(); 
  }
  showAddForm.value = !showAddForm.value;
};

const resetForm = () => {
  form.value = { 
    existing_id: null,
    type: 'MDF', finish_type: '', color_code: '', color_name: '', provider_catalog: '', 
    size_x: 2800, size_y: 2100, thickness: 18, quantity: 10, unit_cost: 400, sell: 550,
    supplier_id: null, amount_paid: 0
  };
  invoiceFile.value = null;
};

const handleExistingSelection = (event) => {
  const id = event.target.value;
  if (!id) {
    resetForm();
    return;
  }
  const p = panels.value.find(panel => panel?.id == id);
  if (p) {
    form.value.existing_id = p.id;
    form.value.type = p.type;
    form.value.finish_type = p.finish_type;
    form.value.color_code = p.color_code;
    form.value.color_name = p.color_name;
    form.value.provider_catalog = p.provider_catalog;
    form.value.size_x = p.size_x;
    form.value.size_y = p.size_y;
    form.value.thickness = p.thickness;
    form.value.sell = p.base_price_sell;
    form.value.unit_cost = p.cost_price;
    form.value.supplier_id = p.supplier_id;
  }
};

const selectColor = (color) => { form.value.color_code = color.code; };

const saveMdf = async () => {
  const calculatedTotal = form.value.quantity * form.value.unit_cost;

  if (!form.value.supplier_id || calculatedTotal <= 0) {
    return alert('Fournisseur et Prix d\'Achat sont obligatoires pour la comptabilité !');
  }

  try {
    if (editingId.value) {
      // Edit mode: Standard panel update
      const payload = { 
        type: form.value.type, 
        finish_type: form.value.finish_type, 
        color_code: form.value.color_code, 
        color_name: form.value.color_name,
        provider_catalog: form.value.provider_catalog, 
        size_x: form.value.size_x, 
        size_y: form.value.size_y, 
        thickness: form.value.thickness, 
        quantity: form.value.quantity, 
        cost_price: form.value.unit_cost, 
        base_price_sell: form.value.sell,
        supplier_id: form.value.supplier_id
      };
      await axios.put(`/api/admin/panels/${editingId.value}`, payload);
      editingId.value = null;
    } else {
      // New Entry: Use Purchase API
      const itemData = {
          type: form.value.type,
          finish_type: form.value.finish_type,
          provider_catalog: form.value.provider_catalog,
          size_x: form.value.size_x,
          size_y: form.value.size_y,
          thickness: form.value.thickness,
          color_code: form.value.color_code || 'N/A',
          color_name: form.value.color_name || '',
          quantity: form.value.quantity,
          cost_price: form.value.unit_cost,
          base_price_sell: form.value.sell
      };

      if (form.value.existing_id) {
          itemData.existing_id = form.value.existing_id;
      }

      const payloadItems = [
        {
          category: 'mdf',
          total_cost: calculatedTotal,
          data: itemData
        }
      ];

      const formData = new FormData();
      formData.append('supplier_id', form.value.supplier_id);
      formData.append('reference_invoice', 'ENTRÉE-STOCK-RAPIDE-' + Date.now());
      formData.append('total_amount', calculatedTotal);
      formData.append('amount_paid', form.value.amount_paid || 0);
      formData.append('payment_method', 'cash');
      formData.append('items', JSON.stringify(payloadItems));
      
      if (invoiceFile.value) {
        formData.append('invoice_document', invoiceFile.value);
      }

      await axios.post('/api/admin/purchases', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('Stock enregistré ET Facture Fournisseur générée avec succès !');
    }
    
    showAddForm.value = false; 
    resetForm(); 
    loadPanels();
  } catch(e) { 
    console.error(e);
    alert('Erreur lors de l\'enregistrement. ' + (e.response?.data?.error || '')); 
  }
};

const editMdf = (pnl) => {
  editingId.value = pnl.id;
  form.value = { 
    type: pnl.type, 
    finish_type: pnl.finish_type, 
    color_code: pnl.color_code, 
    color_name: pnl.color_name,
    provider_catalog: pnl.provider_catalog, 
    size_x: pnl.size_x, 
    size_y: pnl.size_y, 
    thickness: pnl.thickness, 
    quantity: pnl.quantity, 
    unit_cost: pnl.cost_price || 400, 
    amount_paid: 0,
    sell: pnl.base_price_sell,
    supplier_id: pnl.supplier_id
  };
  showAddForm.value = true;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const confirmDeleteMdf = (id) => {
  itemToDelete.value = id;
  deleteModalOpen.value = true;
};

const executeDelete = async () => {
  if (!itemToDelete.value) return;
  try { 
    await axios.delete(`/api/admin/panels/${itemToDelete.value}`); 
    loadPanels(); 
    deleteModalOpen.value = false;
    itemToDelete.value = null;
  } catch(e) { alert('Erreur lors de la suppression'); }
};

const getHexColor = (code) => {
  if (!code) return '#f1f5f9';
  const c = code.toUpperCase();
  const texture = commonTextures.find(t => t.code.toUpperCase() === c);
  if (texture && texture.hex) return texture.hex;
  
  if (c.includes('BLANC')) return '#ffffff';
  if (c.includes('CHENE') || c.includes('TERRA')) return '#b45309';
  if (c.includes('NOIR')) return '#1e293b';
  
  let hash = 0;
  for (let i = 0; i < c.length; i++) { hash = c.charCodeAt(i) + ((hash << 5) - hash); }
  let color = '#';
  for (let i = 0; i < 3; i++) {
    let value = (hash >> (i * 8)) & 0xFF;
    color += ('00' + value.toString(16)).substr(-2);
  }
  return color;
};

// Adjustment (Kosor) Methods
const openAdjustmentModal = () => {
    isAdjustModalOpen.value = true;
};
const closeAdjustmentModal = () => {
    isAdjustModalOpen.value = false;
    adjustForm.value = { item_id: '', quantity: 1, reason: 'kosor', notes: '' };
};
const submitAdjustment = async () => {
    if (!adjustForm.value.item_id || adjustForm.value.quantity <= 0) return;
    try {
        await axios.post('/api/admin/inventory/adjust', {
            item_id: adjustForm.value.item_id,
            item_type: 'StockPanel',
            quantity: adjustForm.value.quantity,
            reason: adjustForm.value.reason,
            notes: adjustForm.value.notes
        });
        alert('Stock ajusté avec succès !');
        closeAdjustmentModal();
        loadPanels();
    } catch (e) {
        console.error(e);
        alert('Erreur lors de l\'ajustement du stock.');
    }
};

const exportData = async (type) => {
    try {
        const res = await axios.post(`/api/export/${type}`, {}, { responseType: 'blob' });
        
        let fileName = `${type}_export_${new Date().getTime()}.xlsx`;
        const disposition = res.headers['content-disposition'] || res.headers['Content-Disposition'];
        
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) { 
                fileName = matches[1].replace(/['"]/g, '');
            }
        }

        // Force extension if missing
        if (!fileName.toLowerCase().endsWith('.xlsx')) {
            fileName += '.xlsx';
        }
        
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        
        setTimeout(() => {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }, 200);
    } catch (e) {
        console.error('Export Error:', e);
        alert('Erreur lors du téléchargement de l\'export');
    }
};

const triggerImport = () => {
    isImportModalOpen.value = true;
};

const downloadStockTemplate = async () => {
    try {
        const res = await axios.get('/api/admin/stock/import-template', { responseType: 'blob' });
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'modele_stock_initial.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error(e);
        alert('Erreur lors du téléchargement du modèle.');
    }
};

const handleImportFile = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    isLoading.value = true;
    try {
        const res = await axios.post('/api/admin/stock/import-initial/panel', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (res.data.success) {
            alert(res.data.message || 'Stock initial importé avec succès !');
            isImportModalOpen.value = false;
            loadPanels();
        } else {
            alert(res.data.message || 'Erreur lors de l\'importation');
        }
    } catch (e) {
        console.error(e);
        alert('Erreur lors de l\'importation : ' + (e.response?.data?.message || e.message));
    } finally {
        isLoading.value = false;
        if (event.target) event.target.value = ''; // Reset input
    }
};

onMounted(() => {
    loadPanels();
    loadSuppliers();
});
</script>

<style scoped>
.shadow-soft { box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-20px); }
</style>
