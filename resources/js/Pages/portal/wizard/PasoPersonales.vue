<script setup lang="ts">
import { User, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { card, lbl, sHead, sIcon, inp } from './wizardStyles';
import { municipios, estadosCiviles, regMatrimonial } from './wizardCatalogos';

const props = defineProps<{
    datos: Record<string, any>;
    camposError: Record<string, string>;
    curpInput: string;
    rfcCurpWarning: boolean;
    fechaMax18: string;
    isOpen: boolean;
}>();
defineEmits<{ toggle: [] }>();

function errClass(campo: string) {
    return props.camposError[campo] ? 'border-red-500 focus:ring-red-200 focus:border-red-500' : '';
}
</script>

<template>
    <div :class="card">
        <div :class="sHead" @click="$emit('toggle')">
            <div :class="sIcon"><User size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Datos personales del solicitante</h2></div>
            <component :is="isOpen ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="isOpen" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2" :data-campo-error="camposError.nombre_completo || undefined"><label :class="lbl">Nombre completo *</label><input v-model="datos.nombre_completo" type="text" maxlength="150" placeholder="Como aparece en tu INE" :class="[inp, errClass('nombre_completo')]" /><p v-if="camposError.nombre_completo" class="text-xs text-red-500 mt-1">{{ camposError.nombre_completo }}</p></div>
            <div><label :class="lbl">CURP *</label><input :value="curpInput.toUpperCase()" readonly :class="[inp,'font-mono tracking-widest bg-slate-50 dark:bg-zinc-800']" /></div>
            <div><label :class="lbl">RFC (con homoclave)</label><input :value="datos.rfc" @input="e => datos.rfc = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,13)" type="text" maxlength="13" :class="[inp,'uppercase font-mono']" /><p v-if="rfcCurpWarning" class="text-xs text-amber-600 mt-1">⚠️ El RFC no parece corresponder al CURP ingresado. Verifica los datos.</p></div>
            <div :data-campo-error="camposError.fecha_nacimiento || undefined"><label :class="lbl">Fecha de nacimiento *</label><input v-model="datos.fecha_nacimiento" type="date" :max="fechaMax18" :class="[inp, errClass('fecha_nacimiento')]" /><p v-if="camposError.fecha_nacimiento" class="text-xs text-red-500 mt-1">{{ camposError.fecha_nacimiento }}</p></div>
            <div :data-campo-error="camposError.sexo || undefined"><label :class="lbl">Sexo *</label>
                <select v-model="datos.sexo" :class="[inp, errClass('sexo')]"><option value="" disabled>Seleccionar</option><option value="M">Masculino</option><option value="F">Femenino</option></select>
                <p v-if="camposError.sexo" class="text-xs text-red-500 mt-1">{{ camposError.sexo }}</p>
            </div>
            <div><label :class="lbl">Lugar de nacimiento</label><input v-model="datos.lugar_nacimiento" type="text" :class="inp" /></div>
            <div><label :class="lbl">Estado civil</label>
                <select v-model="datos.estado_civil" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="e in estadosCiviles" :key="e" :value="e">{{ e }}</option></select>
            </div>
            <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)"><label :class="lbl">Régimen matrimonial</label>
                <select v-model="datos.regimen_matrimonial" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="r in regMatrimonial" :key="r" :value="r">{{ r }}</option></select>
            </div>
            <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)"><label :class="lbl">Nombre del cónyuge</label><input v-model="datos.nombre_conyuge" type="text" :class="inp" /></div>
            <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)"><label :class="lbl">CURP del cónyuge</label><input :value="datos.curp_conyuge" @input="e => datos.curp_conyuge = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,18)" type="text" maxlength="18" :class="[inp,'uppercase font-mono']" /></div>
            <div class="sm:col-span-2" :data-campo-error="camposError.direccion || undefined"><label :class="lbl">Dirección (calle y número) *</label><input v-model="datos.direccion" type="text" maxlength="200" :class="[inp, errClass('direccion')]" /><p v-if="camposError.direccion" class="text-xs text-red-500 mt-1">{{ camposError.direccion }}</p></div>
            <div><label :class="lbl">Colonia</label><input v-model="datos.colonia" type="text" maxlength="100" :class="inp" /></div>
            <div><label :class="lbl">C.P.</label><input v-model="datos.cp" type="text" maxlength="5" :class="inp" /></div>
            <div :data-campo-error="camposError.municipio || undefined"><label :class="lbl">Municipio *</label>
                <select v-model="datos.municipio" :class="[inp, errClass('municipio')]"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select>
                <p v-if="camposError.municipio" class="text-xs text-red-500 mt-1">{{ camposError.municipio }}</p>
            </div>
            <div>
                <label :class="lbl">Domicilio</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-sm min-h-[44px]"><input type="radio" :value="true" v-model="datos.domicilio_propio" class="text-[#6B1938]" /> Propio</label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm min-h-[44px]"><input type="radio" :value="false" v-model="datos.domicilio_propio" class="text-[#6B1938]" /> Rentado</label>
                </div>
            </div>
            <div v-if="!datos.domicilio_propio"><label :class="lbl">Renta mensual ($)</label><input v-model="datos.renta_mensual" type="number" min="0" :class="inp" /></div>
            <div :data-campo-error="camposError.telefono || undefined"><label :class="lbl">Teléfono celular *</label><input :value="datos.telefono" @input="e => datos.telefono = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" placeholder="10 dígitos" :class="[inp, errClass('telefono')]" /><p v-if="camposError.telefono" class="text-xs text-red-500 mt-1">{{ camposError.telefono }}</p></div>
            <div><label :class="lbl">Teléfono fijo</label><input :value="datos.telefono_fijo" @input="e => datos.telefono_fijo = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
            <div class="sm:col-span-2" :data-campo-error="camposError.correo || undefined"><label :class="lbl">Correo electrónico *</label><input v-model="datos.correo" type="email" :class="[inp, errClass('correo')]" /><p v-if="camposError.correo" class="text-xs text-red-500 mt-1">{{ camposError.correo }}</p></div>
            <div class="sm:col-span-2 flex flex-wrap gap-5">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.mayahablante" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Habla Maya?</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.discapacidad" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Tiene discapacidad?</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.empleado_gobierno" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Empleado gobierno?</span>
                </label>
            </div>
            <div v-if="datos.discapacidad"><label :class="lbl">Tipo de discapacidad</label><input v-model="datos.discapacidad_tipo" type="text" :class="inp" /></div>
            <div v-if="datos.empleado_gobierno"><label :class="lbl">Dependencia</label><input v-model="datos.dependencia_gobierno" type="text" :class="inp" /></div>
            <div v-if="datos.empleado_gobierno"><label :class="lbl">Puesto</label><input v-model="datos.puesto_gobierno" type="text" :class="inp" /></div>
            <div class="sm:col-span-2 pt-3 border-t border-slate-100 dark:border-zinc-800">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Referencia personal (no familiar)</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div><label :class="lbl">Nombre</label><input v-model="datos.referencia_nombre" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Teléfono</label><input :value="datos.referencia_telefono" @input="e => datos.referencia_telefono = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
                    <div><label :class="lbl">C.P.</label><input v-model="datos.referencia_cp" type="text" maxlength="5" :class="inp" /></div>
                </div>
            </div>
        </div>
    </div>
</template>
