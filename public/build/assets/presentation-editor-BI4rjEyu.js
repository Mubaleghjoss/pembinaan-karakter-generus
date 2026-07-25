import{d as q,p as v,r as S,f as $,a as C,b as N}from"./presentation-canvas-CPtLzb2c.js";const l=document.getElementById("presentation-editor");if(l){const i=q(l.dataset.presentationPayload),e={presentation:i,selectedFrameId:i.canvas.frames[0]?.id||null,selectedElementId:null,mode:"overview",dirty:!1,saving:!1,cameraScale:1,drag:null},r={viewport:l.querySelector("[data-editor-viewport]"),stage:l.querySelector("[data-editor-stage]"),frameList:l.querySelector("[data-frame-list]"),inspector:l.querySelector("[data-editor-inspector]"),hint:l.querySelector("[data-editor-hint]"),saveStatus:l.querySelector("[data-save-status]"),title:l.querySelector("[data-editor-title]"),description:l.querySelector("[data-editor-description]"),background:l.querySelector("[data-editor-background]"),pathMode:l.querySelector("[data-editor-path-mode]"),imageInput:l.querySelector("[data-image-input]")};r.title.value=i.title||"",r.description.value=i.description||"",r.background.value=i.backgroundColor||"#0f172a",r.pathMode.value=i.pathMode||"overview_between";const c=()=>{e.dirty=!0,r.saveStatus.textContent="Belum disimpan",r.saveStatus.classList.add("text-amber-600","dark:text-amber-300")},p=()=>$(e.presentation,e.selectedFrameId),w=()=>C(p(),e.selectedElementId),h=(a=!0)=>{const t=e.mode==="focus"?p():null;e.cameraScale=N(r.viewport,r.stage,e.presentation.canvas,t,a),r.hint.textContent=t?`Fokus: ${t.title}`:"Mode Overview"},m=(a=!0)=>{S({stage:r.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),g(),y(),requestAnimationFrame(()=>h(a))},g=()=>{r.frameList.replaceChildren(),e.presentation.canvas.frames.forEach((a,t)=>{const n=document.createElement("div");n.className=`pkg-presentation-frame-list-item${a.id===e.selectedFrameId?" is-selected":""}`;const s=document.createElement("button");s.type="button",s.className="min-w-0 flex-1 text-left",s.dataset.frameFocus=a.id,s.innerHTML=`<span class="block text-xs font-bold text-emerald-600">${t+1}</span>`;const o=document.createElement("span");o.className="block truncate text-sm font-semibold text-gray-900 dark:text-white",o.textContent=a.title,s.appendChild(o),n.appendChild(s);const d=document.createElement("div");d.className="flex gap-1",d.innerHTML=`
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="up" data-frame-id="${a.id}" aria-label="Naik">↑</button>
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="down" data-frame-id="${a.id}" aria-label="Turun">↓</button>
            `,n.appendChild(d),r.frameList.appendChild(n)})},x=(a,t=!1)=>`
        <div class="grid grid-cols-2 gap-3">
            ${f("X","x",a.x,0,5e3)}
            ${f("Y","y",a.y,0,t?11e3:1100)}
            ${f("Lebar","width",a.width,40,1600)}
            ${f("Tinggi","height",a.height,30,900)}
        </div>
    `,y=()=>{const a=p(),t=w();if(!a){r.inspector.innerHTML='<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';return}if(!t){r.inspector.innerHTML=`
                <div class="space-y-4" data-inspector-scope="frame">
                    <div>
                        <label class="form-label">Judul frame</label>
                        <input class="pkg-field w-full" maxlength="120" data-inspector-prop="title" value="${F(a.title)}">
                    </div>
                    <div>
                        <label class="form-label">Warna frame</label>
                        <input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="backgroundColor" value="${a.backgroundColor||"#ffffff"}">
                    </div>
                    ${x(a,!0)}
                    <button type="button" class="btn-primary w-full justify-center" data-focus-selected-frame>Fokuskan Frame</button>
                    <button type="button" class="btn-danger w-full justify-center" data-delete-selected-frame ${e.presentation.canvas.frames.length<=1?"disabled":""}>Hapus Frame</button>
                </div>
            `;return}let n="";t.type==="text"?n=`
                <div>
                    <label class="form-label">Isi teks</label>
                    <textarea class="pkg-field w-full" rows="5" maxlength="5000" data-inspector-prop="text">${k(t.text||"")}</textarea>
                </div>
                ${f("Ukuran huruf","fontSize",t.fontSize||32,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${b("Warna teks","color",t.color||"#0f172a")}
                    ${b("Latar","backgroundColor",L(t.backgroundColor,"#ffffff"))}
                </div>
                <div>
                    <label class="form-label">Perataan</label>
                    <select class="pkg-field w-full" data-inspector-prop="align">
                        ${u("left","Kiri",t.align)}
                        ${u("center","Tengah",t.align)}
                        ${u("right","Kanan",t.align)}
                    </select>
                </div>
                <label class="pkg-check"><input type="checkbox" data-inspector-prop="bold" ${t.bold?"checked":""}><span>Teks tebal</span></label>
            `:t.type==="image"?n=`
                <div>
                    <label class="form-label">Teks alternatif</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="alt" value="${F(t.alt||"")}">
                </div>
                <div>
                    <label class="form-label">Penyesuaian gambar</label>
                    <select class="pkg-field w-full" data-inspector-prop="fit">
                        ${u("cover","Penuhi area",t.fit)}
                        ${u("contain","Tampilkan utuh",t.fit)}
                    </select>
                </div>
            `:n=`
                <div>
                    <label class="form-label">Isi diagram (satu baris per kotak)</label>
                    <textarea class="pkg-field w-full" rows="6" data-inspector-prop="items">${k((t.items||[]).join(`
`))}</textarea>
                </div>
                <div>
                    <label class="form-label">Bentuk alur</label>
                    <select class="pkg-field w-full" data-inspector-prop="diagramType">
                        ${u("process","Proses mendatar",t.diagramType)}
                        ${u("cycle","Siklus",t.diagramType)}
                        ${u("hierarchy","Hierarki",t.diagramType)}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${b("Warna diagram","color",t.color||"#0f172a")}
                    ${b("Latar","backgroundColor",L(t.backgroundColor,"#ffffff"))}
                </div>
            `,r.inspector.innerHTML=`
            <div class="space-y-4" data-inspector-scope="element">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${X(t.type)}
                </div>
                ${n}
                ${x(t)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `},M=a=>{e.selectedFrameId=a,e.selectedElementId=null,e.mode="focus",m()};l.querySelector("[data-editor-overview]").addEventListener("click",()=>{e.mode="overview",e.selectedElementId=null,m()}),l.querySelector("[data-add-frame]").addEventListener("click",()=>{const a=e.presentation.canvas.frames.length,t=180+a%2*1100,n=180+Math.floor(a/2)*560,s={id:v("frame"),title:`Frame ${a+1}`,x:t,y:n,width:800,height:450,backgroundColor:"#ffffff",elements:[]};e.presentation.canvas.frames.push(s),e.presentation.canvas.height=Math.max(e.presentation.canvas.height,n+650),e.selectedFrameId=s.id,e.selectedElementId=null,e.mode="overview",c(),m()}),l.querySelector("[data-add-text]").addEventListener("click",()=>{const a=p();if(!a)return;const t={id:v("element"),type:"text",x:70,y:80,width:Math.max(240,a.width-140),height:130,rotation:0,text:"Tulis materi di sini",fontSize:36,color:"#0f172a",backgroundColor:"transparent",align:"left",bold:!1};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",c(),m()}),l.querySelector("[data-add-diagram]").addEventListener("click",()=>{const a=p();if(!a)return;const t={id:v("element"),type:"diagram",x:70,y:130,width:Math.max(360,a.width-140),height:180,rotation:0,color:"#047857",backgroundColor:"transparent",diagramType:"process",items:["Pembuka","Pembahasan","Kesimpulan"]};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",c(),m()}),l.querySelector("[data-add-image]").addEventListener("click",()=>{p()&&r.imageInput.click()}),r.imageInput.addEventListener("change",async()=>{const a=r.imageInput.files?.[0],t=p();if(!a||!t)return;const n=new FormData;n.append("image",a),r.saveStatus.textContent="Mengunggah gambar...";try{const s=await fetch(l.dataset.uploadUrl,{method:"POST",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:n}),o=await s.json();if(!s.ok)throw new Error(o.message||"Gambar gagal diunggah.");e.presentation.assets[String(o.asset.id)]=o.asset;const d={id:v("element"),type:"image",assetId:o.asset.id,x:90,y:80,width:Math.min(420,t.width-180),height:Math.min(280,t.height-140),rotation:0,alt:o.asset.name,fit:"cover",color:"#0f172a",backgroundColor:"transparent"};t.elements.push(d),e.selectedElementId=d.id,e.mode="focus",c(),m()}catch(s){r.saveStatus.textContent=s.message,r.saveStatus.classList.add("text-red-600")}finally{r.imageInput.value=""}}),r.frameList.addEventListener("click",a=>{const t=a.target.closest("[data-frame-focus]");if(t){M(t.dataset.frameFocus);return}const n=a.target.closest("[data-frame-move]");if(!n)return;const s=e.presentation.canvas.frames,o=s.findIndex(T=>T.id===n.dataset.frameId),d=n.dataset.frameMove==="up"?o-1:o+1;o<0||d<0||d>=s.length||([s[o],s[d]]=[s[d],s[o]],c(),g())}),r.inspector.addEventListener("input",a=>{const t=a.target.closest("[data-inspector-prop]");if(!t)return;const n=t.closest("[data-inspector-scope]")?.dataset.inspectorScope,s=n==="frame"?p():w();if(!s)return;let o=t.type==="checkbox"?t.checked:t.value;["x","y","width","height","fontSize"].includes(t.dataset.inspectorProp)&&(o=Number(o)),t.dataset.inspectorProp==="items"&&(o=String(o).split(`
`).map(d=>d.trim()).filter(Boolean).slice(0,8)),s[t.dataset.inspectorProp]=o,c(),S({stage:r.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),h(!1),n==="frame"&&t.dataset.inspectorProp==="title"&&g()}),r.inspector.addEventListener("click",a=>{if(a.target.closest("[data-focus-selected-frame]")){e.mode="focus",m();return}if(a.target.closest("[data-delete-selected-element]")){const t=p();t.elements=t.elements.filter(n=>n.id!==e.selectedElementId),e.selectedElementId=null,c(),m();return}if(a.target.closest("[data-delete-selected-frame]")&&e.presentation.canvas.frames.length>1){const t=e.presentation.canvas.frames,n=t.findIndex(s=>s.id===e.selectedFrameId);t.splice(n,1),e.selectedFrameId=t[Math.max(0,n-1)]?.id||t[0]?.id,e.selectedElementId=null,e.mode="overview",c(),m()}});const E=()=>{e.presentation.title=r.title.value,e.presentation.description=r.description.value,e.presentation.backgroundColor=r.background.value,e.presentation.pathMode=r.pathMode.value,c(),document.activeElement===r.background&&m(!1)};[r.title,r.description,r.background,r.pathMode].forEach(a=>{a.addEventListener("input",E),a.addEventListener("change",E)}),r.viewport.addEventListener("pointerdown",a=>{if(a.button!==0)return;const t=a.target.closest("[data-frame-id]"),n=a.target.closest("[data-element-id]");if(!t)return;const s=$(e.presentation,t.dataset.frameId),o=C(s,n?.dataset.elementId);e.selectedFrameId=s.id,e.selectedElementId=e.mode==="focus"&&o?o.id:null,e.drag={kind:e.mode==="overview"?"frame":o?"element":null,target:e.mode==="overview"?s:o,node:e.mode==="overview"?t:n,startX:a.clientX,startY:a.clientY,originX:e.mode==="overview"?s.x:o?.x,originY:e.mode==="overview"?s.y:o?.y,moved:!1},r.viewport.setPointerCapture(a.pointerId),y()}),r.viewport.addEventListener("pointermove",a=>{if(!e.drag?.kind||!e.drag.target)return;const t=(a.clientX-e.drag.startX)/e.cameraScale,n=(a.clientY-e.drag.startY)/e.cameraScale;Math.abs(t)+Math.abs(n)>2&&(e.drag.moved=!0),e.drag.target.x=Math.max(0,e.drag.originX+t),e.drag.target.y=Math.max(0,e.drag.originY+n),e.drag.node.style.left=`${e.drag.target.x}px`,e.drag.node.style.top=`${e.drag.target.y}px`});const I=a=>{if(!e.drag)return;const t=e.drag;if(e.drag=null,r.viewport.hasPointerCapture(a.pointerId)&&r.viewport.releasePointerCapture(a.pointerId),t.moved)c(),y();else if(e.mode==="overview"){e.mode="focus",m();return}g()};r.viewport.addEventListener("pointerup",I),r.viewport.addEventListener("pointercancel",I);const P=async()=>{if(!e.saving){e.saving=!0,r.saveStatus.textContent="Menyimpan...";try{const a=await fetch(l.dataset.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:JSON.stringify({title:r.title.value,description:r.description.value,background_color:r.background.value,path_mode:r.pathMode.value,canvas_data:e.presentation.canvas})}),t=await a.json();if(!a.ok)throw new Error(t.message||Object.values(t.errors||{})[0]?.[0]||"Presentasi gagal disimpan.");e.dirty=!1,r.saveStatus.textContent="Semua perubahan tersimpan",r.saveStatus.classList.remove("text-amber-600","dark:text-amber-300","text-red-600")}catch(a){r.saveStatus.textContent=a.message,r.saveStatus.classList.add("text-red-600")}finally{e.saving=!1}}};l.querySelector("[data-editor-save]").addEventListener("click",P),window.addEventListener("beforeunload",a=>{e.dirty&&(a.preventDefault(),a.returnValue="")}),new ResizeObserver(()=>h(!1)).observe(r.viewport),m(!1)}function f(i,e,r,c,p){return`<div><label class="form-label">${i}</label><input type="number" class="pkg-field w-full" min="${c}" max="${p}" data-inspector-prop="${e}" value="${Math.round(r)}"></div>`}function b(i,e,r){return`<div><label class="form-label">${i}</label><input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="${e}" value="${r}"></div>`}function u(i,e,r){return`<option value="${i}" ${i===r?"selected":""}>${e}</option>`}function L(i,e){return/^#[0-9a-f]{6}$/i.test(i||"")?i:e}function X(i){return{text:"Elemen Teks",image:"Elemen Gambar",diagram:"Elemen Diagram"}[i]||"Elemen"}function k(i){return String(i??"").replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"})[e])}function F(i){return k(i).replace(/\n/g,"&#10;")}
