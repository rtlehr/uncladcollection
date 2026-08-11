import { createApp } from 'vue';
import MessageBoxHost from '@/components/MessageBoxes/MessageBoxHost.vue';
let mounted=false;
export function initializeMessageBoxes():void {
    if(mounted || typeof document==='undefined') return;
    const point=document.createElement('div'); point.id='managed-message-box-host'; document.body.appendChild(point);
    createApp(MessageBoxHost).mount(point); mounted=true;
}
export function triggerMessageBox(key:string):void {
    window.dispatchEvent(new CustomEvent('message-box:trigger',{detail:key}));
}
