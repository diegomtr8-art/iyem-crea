export const inp = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-2 focus:ring-[#6B1938]/20 focus:border-[#6B1938] transition-all text-slate-900 dark:text-white placeholder:text-slate-400 text-sm bg-white min-h-[44px]';
export const lbl = 'block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-1';
export const card = 'bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 shadow-sm overflow-hidden';
export const sHead = 'flex items-center gap-3 px-5 py-4 cursor-pointer select-none border-b border-slate-100 dark:border-zinc-800';
export const sIcon = 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-[#6B1938]/10 dark:bg-[#6B1938]/20 text-[#6B1938] dark:text-[#f4a8c4]';

export const fmt = (v: any) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(+(v || 0));

export const addRow = (arr: any[], tpl: any) => arr.push({ ...tpl });
export const removeRow = (arr: any[], i: number) => arr.splice(i, 1);
