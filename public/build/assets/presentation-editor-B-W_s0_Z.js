import{d as U,p as $,r as W,z as D,a as V,b as G,c as O,f as A,e as K,g as k,h as _}from"./presentation-canvas-ADo0RNdO.js";const o=document.getElementById("presentation-editor");if(o){const c=U(o.dataset.presentationPayload),e={presentation:c,selectedFrameId:c.canvas.frames[0]?.id||null,selectedElementId:null,mode:"overview",dirty:!1,saving:!1,changeVersion:0,saveTimer:null,activeSave:null,cameraScale:1,manualCamera:!1,drag:null},r={viewport:o.querySelector("[data-editor-viewport]"),stage:o.querySelector("[data-editor-stage]"),frameList:o.querySelector("[data-frame-list]"),inspector:o.querySelector("[data-editor-inspector]"),hint:o.querySelector("[data-editor-hint]"),saveStatus:o.querySelector("[data-save-status]"),title:o.querySelector("[data-editor-title]"),description:o.querySelector("[data-editor-description]"),background:o.querySelector("[data-editor-background]"),pathMode:o.querySelector("[data-editor-path-mode]"),imageInput:o.querySelector("[data-image-input]"),logoInput:o.querySelector("[data-logo-input]")};let C=null;r.title.value=c.title||"",r.description.value=c.description||"",r.background.value=c.backgroundColor||"#0f172a",r.pathMode.value=c.pathMode||"overview_between";const z=()=>{window.clearTimeout(e.saveTimer),e.saveTimer=window.setTimeout(()=>S(),1200)},f=()=>{e.dirty=!0,e.changeVersion+=1,r.saveStatus.textContent="Belum disimpan · akan disimpan otomatis",r.saveStatus.classList.add("text-amber-600","dark:text-amber-300"),z()},g=()=>A(e.presentation,e.selectedFrameId),N=()=>K(g(),e.selectedElementId),v=()=>{const a=e.presentation.canvas.frames,t=a.reduce((n,l)=>Math.max(n,Number(l.x||0)+Number(l.width||0)+120),1200),i=a.reduce((n,l)=>Math.max(n,Number(l.y||0)+Number(l.height||0)+120),800);e.presentation.canvas.width=k(t,1200,7e3,2400),e.presentation.canvas.height=k(i,800,12500,1400)},F=(a,t,i)=>{const n=Math.max(1,Number(a.width||800)),l=Math.max(1,Number(a.height||450)),s=k(t,320,1600,n),p=k(i,180,900,l),h=s/n,u=p/l,y=Math.min(h,u);(a.elements||[]).forEach(b=>{b.x=Number(b.x||0)*h,b.y=Number(b.y||0)*u,b.width=Math.max(40,Number(b.width||100)*h),b.height=Math.max(30,Number(b.height||80)*u),b.type==="text"&&(b.fontSize=k(Number(b.fontSize||32)*y,10,160,32))}),a.width=s,a.height=p,v()};v();const I=()=>{const a=e.mode==="focus"?g():null,t=a?`Fokus: ${a.title}`:"Mode Overview";r.hint.textContent=`${t} · ${Math.round(e.cameraScale*100)}%`},T=(a=!0)=>{const t=e.mode==="focus"?g():null;e.cameraScale=_(r.viewport,r.stage,e.presentation.canvas,t,a),e.manualCamera=!1,I()},m=(a=!0)=>{W({stage:r.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),L(),q(),requestAnimationFrame(()=>{if(e.manualCamera){I();return}T(a)})},L=()=>{r.frameList.replaceChildren(),e.presentation.canvas.frames.forEach((a,t)=>{const i=document.createElement("div");i.className=`pkg-presentation-frame-list-item${a.id===e.selectedFrameId?" is-selected":""}`;const n=document.createElement("button");n.type="button",n.className="min-w-0 flex-1 text-left",n.dataset.frameFocus=a.id,n.innerHTML=`<span class="block text-xs font-bold text-emerald-600">${t+1}</span>`;const l=document.createElement("span");l.className="block truncate text-sm font-semibold text-gray-900 dark:text-white",l.textContent=a.title,n.appendChild(l),i.appendChild(n);const s=document.createElement("div");s.className="flex gap-1",s.innerHTML=`
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="up" data-frame-id="${a.id}" aria-label="Naik">↑</button>
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="down" data-frame-id="${a.id}" aria-label="Turun">↓</button>
            `,i.appendChild(s),r.frameList.appendChild(i)})},H=(a,t=!1)=>`
        <div class="grid grid-cols-2 gap-3">
            ${x("X","x",a.x,0,5e3)}
            ${x("Y","y",a.y,0,t?11e3:1100)}
            ${x("Lebar","width",a.width,t?320:40,1600)}
            ${x("Tinggi","height",a.height,t?180:30,900)}
        </div>
    `,q=()=>{const a=g(),t=N();if(!a){r.inspector.innerHTML='<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';return}if(!t){r.inspector.innerHTML=`
                <div class="space-y-4" data-inspector-scope="frame">
                    <div>
                        <label class="form-label">Judul frame</label>
                        <input class="pkg-field w-full" maxlength="120" data-inspector-prop="title" value="${E(a.title)}">
                    </div>
                    <div>
                        <label class="form-label">Warna frame</label>
                        <input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="backgroundColor" value="${a.backgroundColor||"#ffffff"}">
                    </div>
                    <div>
                        <label class="form-label">Bentuk frame</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${d("rounded","Sudut membulat",a.shape||"rounded")}
                            ${d("rectangle","Kotak",a.shape)}
                            ${d("circle","Lingkaran / oval",a.shape)}
                            ${d("hexagon","Segi enam",a.shape)}
                            ${d("custom","Radius buatan sendiri",a.shape)}
                        </select>
                    </div>
                    ${a.shape==="custom"?x("Radius sudut","borderRadius",a.borderRadius||22,0,240):""}
                    <div>
                        <label class="form-label">Ukuran frame</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="pkg-frame-size-button" data-frame-size="560x315">Kecil</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="800x450">Sedang</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="1120x630">Besar</button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Gunakan preset, isi ukuran manual, atau seret pegangan hijau di sudut kanan bawah frame.</p>
                    </div>
                    ${H(a,!0)}
                    <button type="button" class="btn-primary w-full justify-center" data-focus-selected-frame>Fokuskan Frame</button>
                    <button type="button" class="btn-danger w-full justify-center" data-delete-selected-frame ${e.presentation.canvas.frames.length<=1?"disabled":""}>Hapus Frame</button>
                </div>
            `;return}let i="";t.type==="text"?i=`
                <div>
                    <label class="form-label">Isi teks</label>
                    <textarea class="pkg-field w-full" rows="5" maxlength="5000" data-inspector-prop="text">${P(t.text||"")}</textarea>
                </div>
                ${x("Ukuran huruf","fontSize",t.fontSize||32,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna teks","color",t.color||"#0f172a")}
                    ${w("Latar","backgroundColor",M(t.backgroundColor,"#ffffff"))}
                </div>
                <div>
                    <label class="form-label">Perataan</label>
                    <select class="pkg-field w-full" data-inspector-prop="align">
                        ${d("left","Kiri",t.align)}
                        ${d("center","Tengah",t.align)}
                        ${d("right","Kanan",t.align)}
                    </select>
                </div>
                <label class="pkg-check"><input type="checkbox" data-inspector-prop="bold" ${t.bold?"checked":""}><span>Teks tebal</span></label>
            `:t.type==="image"||t.type==="logo"?i=`
                <div>
                    <label class="form-label">Teks alternatif</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="alt" value="${E(t.alt||"")}">
                </div>
                <div>
                    <label class="form-label">Penyesuaian gambar</label>
                    <select class="pkg-field w-full" data-inspector-prop="fit">
                        ${d("cover","Penuhi area",t.fit)}
                        ${d("contain","Tampilkan utuh",t.fit)}
                    </select>
                </div>
                ${t.type==="logo"?`
                    <div>
                        <label class="form-label">Bentuk logo</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${d("circle","Lingkaran",t.shape||"circle")}
                            ${d("rounded","Sudut membulat",t.shape)}
                            ${d("square","Kotak",t.shape)}
                            ${d("hexagon","Segi enam",t.shape)}
                        </select>
                    </div>
                `:""}
            `:t.type==="youtube"?i=`
                <div>
                    <label class="form-label">Link YouTube</label>
                    <input type="url" class="pkg-field w-full" maxlength="500" data-inspector-prop="youtubeUrl" value="${E(t.youtubeUrl||"")}" placeholder="https://youtu.be/...">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Video dapat diputar dan dibuka layar penuh pada Pratinjau atau Tautan Publik.</p>
                </div>
                <div>
                    <label class="form-label">Judul video</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="title" value="${E(t.title||"")}">
                </div>
            `:t.type==="link"?i=`
                <div><label class="form-label">Label tautan</label><input class="pkg-field w-full" maxlength="160" data-inspector-prop="text" value="${E(t.text||"")}"></div>
                <div><label class="form-label">Alamat tautan</label><input type="url" class="pkg-field w-full" maxlength="1000" data-inspector-prop="url" value="${E(t.url||"")}" placeholder="https://..."></div>
                <div>
                    <label class="form-label">Tampilan tautan</label>
                    <select class="pkg-field w-full" data-inspector-prop="linkStyle">
                        ${d("button","Tombol",t.linkStyle)}
                        ${d("card","Kartu",t.linkStyle)}
                        ${d("text","Teks",t.linkStyle)}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna teks","color",t.color||"#ffffff")}
                    ${w("Latar","backgroundColor",M(t.backgroundColor,"#047857"))}
                </div>
            `:t.type==="shape"?i=`
                <div><label class="form-label">Teks di bentuk</label><textarea class="pkg-field w-full" rows="3" maxlength="1000" data-inspector-prop="text">${P(t.text||"")}</textarea></div>
                <div>
                    <label class="form-label">Bentuk</label>
                    <select class="pkg-field w-full" data-inspector-prop="shapeType">
                        ${d("circle","Lingkaran / oval",t.shapeType)}
                        ${d("rounded","Sudut membulat",t.shapeType)}
                        ${d("rectangle","Kotak",t.shapeType)}
                        ${d("hexagon","Segi enam",t.shapeType)}
                        ${d("custom","Radius buatan sendiri",t.shapeType)}
                    </select>
                </div>
                ${t.shapeType==="custom"?x("Radius sudut","borderRadius",t.borderRadius||24,0,240):""}
                ${x("Ukuran huruf","fontSize",t.fontSize||28,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna teks","color",t.color||"#ffffff")}
                    ${w("Warna bentuk","backgroundColor",M(t.backgroundColor,"#0f766e"))}
                </div>
            `:i=`
                <div>
                    <label class="form-label">Isi diagram (satu baris per kotak)</label>
                    <textarea class="pkg-field w-full" rows="6" data-inspector-prop="items">${P((t.items||[]).join(`
`))}</textarea>
                </div>
                <div>
                    <label class="form-label">Bentuk alur</label>
                    <select class="pkg-field w-full" data-inspector-prop="diagramType">
                        ${d("process","Proses mendatar",t.diagramType)}
                        ${d("cycle","Siklus",t.diagramType)}
                        ${d("hierarchy","Hierarki",t.diagramType)}
                        ${d("radial","Radial dengan pusat",t.diagramType)}
                    </select>
                </div>
                ${t.diagramType==="radial"?`
                    <div><label class="form-label">Teks pusat / logo</label><input class="pkg-field w-full" maxlength="120" data-inspector-prop="centerText" value="${E(t.centerText||"")}"></div>
                    <div>
                        <label class="form-label">Bentuk node</label>
                        <select class="pkg-field w-full" data-inspector-prop="nodeShape">
                            ${d("circle","Lingkaran",t.nodeShape||"circle")}
                            ${d("rounded","Sudut membulat",t.nodeShape)}
                            ${d("square","Kotak",t.nodeShape)}
                            ${d("hexagon","Segi enam",t.nodeShape)}
                        </select>
                    </div>
                `:""}
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna diagram","color",t.color||"#0f172a")}
                    ${w("Latar","backgroundColor",M(t.backgroundColor,"#ffffff"))}
                </div>
            `,r.inspector.innerHTML=`
            <div class="space-y-4" data-inspector-scope="element">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${J(t.type)}
                </div>
                ${i}
                ${H(t)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `},Y=a=>{e.selectedFrameId=a,e.selectedElementId=null,e.mode="focus",e.manualCamera=!1,m()};o.querySelector("[data-editor-overview]").addEventListener("click",()=>{e.mode="overview",e.selectedElementId=null,e.manualCamera=!1,m()}),o.querySelector("[data-editor-fit]").addEventListener("click",()=>{e.manualCamera=!1,T()}),o.querySelector("[data-add-frame]").addEventListener("click",()=>{const a=e.presentation.canvas.frames.length,t=180+a%2*1100,i=180+Math.floor(a/2)*560,n={id:$("frame"),title:`Frame ${a+1}`,x:t,y:i,width:800,height:450,backgroundColor:"#ffffff",shape:"rounded",borderRadius:22,elements:[]};e.presentation.canvas.frames.push(n),v(),e.selectedFrameId=n.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,f(),m()}),o.querySelector("[data-arrange-frames]").addEventListener("click",()=>{const a=e.presentation.canvas.frames,t=Math.max(...a.map(s=>Number(s.width||800))),i=Math.max(...a.map(s=>Number(s.height||450))),n=160,l=140;a.forEach((s,p)=>{s.x=120+p%2*(t+n),s.y=120+Math.floor(p/2)*(i+l)}),e.mode="overview",e.selectedElementId=null,e.manualCamera=!1,v(),f(),m()}),o.querySelector("[data-add-text]").addEventListener("click",()=>{const a=g();if(!a)return;const t={id:$("element"),type:"text",x:70,y:80,width:Math.max(240,a.width-140),height:130,rotation:0,text:"Tulis materi di sini",fontSize:36,color:"#0f172a",backgroundColor:"transparent",align:"left",bold:!1};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),m()}),o.querySelector("[data-add-diagram]").addEventListener("click",()=>{const a=g();if(!a)return;const t={id:$("element"),type:"diagram",x:70,y:130,width:Math.max(360,a.width-140),height:180,rotation:0,color:"#047857",backgroundColor:"transparent",diagramType:"process",items:["Pembuka","Pembahasan","Kesimpulan"]};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),m()}),o.querySelector("[data-add-youtube]").addEventListener("click",()=>{const a=g();if(!a)return;const t={id:$("element"),type:"youtube",x:90,y:90,width:Math.min(560,a.width-180),height:Math.min(315,a.height-150),rotation:0,youtubeUrl:"",youtubeId:"",title:"Video YouTube",color:"#ffffff",backgroundColor:"#0f172a"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),m()}),o.querySelector("[data-add-link]").addEventListener("click",()=>{const a=g();if(!a)return;const t={id:$("element"),type:"link",x:90,y:300,width:260,height:70,rotation:0,text:"Buka tautan",url:"https://",linkStyle:"button",color:"#ffffff",backgroundColor:"#047857"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),m()}),o.querySelector("[data-add-shape]").addEventListener("click",()=>{const a=g();if(!a)return;const t={id:$("element"),type:"shape",x:110,y:140,width:220,height:150,rotation:0,text:"Isi bentuk",shapeType:"rounded",borderRadius:24,fontSize:28,color:"#ffffff",backgroundColor:"#0f766e"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),m()}),o.querySelector("[data-add-image]").addEventListener("click",()=>{g()&&r.imageInput.click()}),o.querySelector("[data-add-logo]").addEventListener("click",()=>{g()&&r.logoInput.click()});const X=async(a,t)=>{const i=a.files?.[0],n=g();if(!i||!n)return;const l=new FormData;l.append("image",i),r.saveStatus.textContent="Mengunggah gambar...";try{const s=await fetch(o.dataset.uploadUrl,{method:"POST",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:l}),p=await s.json();if(!s.ok)throw new Error(p.message||"Gambar gagal diunggah.");e.presentation.assets[String(p.asset.id)]=p.asset;const h={id:$("element"),type:t,assetId:p.asset.id,x:90,y:80,width:Math.min(t==="logo"?220:420,n.width-180),height:Math.min(t==="logo"?220:280,n.height-140),rotation:0,alt:p.asset.name,fit:t==="logo"?"contain":"cover",shape:t==="logo"?"circle":"rounded",color:"#0f172a",backgroundColor:"transparent"};n.elements.push(h),e.selectedElementId=h.id,e.mode="focus",e.manualCamera=!1,f(),m()}catch(s){r.saveStatus.textContent=s.message,r.saveStatus.classList.add("text-red-600")}finally{a.value=""}};r.imageInput.addEventListener("change",()=>X(r.imageInput,"image")),r.logoInput.addEventListener("change",()=>X(r.logoInput,"logo")),r.frameList.addEventListener("click",a=>{const t=a.target.closest("[data-frame-focus]");if(t){Y(t.dataset.frameFocus);return}const i=a.target.closest("[data-frame-move]");if(!i)return;const n=e.presentation.canvas.frames,l=n.findIndex(p=>p.id===i.dataset.frameId),s=i.dataset.frameMove==="up"?l-1:l+1;l<0||s<0||s>=n.length||([n[l],n[s]]=[n[s],n[l]],f(),L())}),r.inspector.addEventListener("input",a=>{const t=a.target.closest("[data-inspector-prop]");if(!t)return;const i=t.closest("[data-inspector-scope]")?.dataset.inspectorScope,n=i==="frame"?g():N();if(!n||i==="frame"&&["width","height"].includes(t.dataset.inspectorProp))return;let l=t.type==="checkbox"?t.checked:t.value;["x","y","width","height","fontSize","borderRadius"].includes(t.dataset.inspectorProp)&&(l=Number(l)),t.dataset.inspectorProp==="items"&&(l=String(l).split(`
`).map(s=>s.trim()).filter(Boolean).slice(0,8)),n[t.dataset.inspectorProp]=l,t.dataset.inspectorProp==="youtubeUrl"&&(n.youtubeId=Z(l)),i==="frame"&&v(),f(),W({stage:r.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),e.manualCamera?I():T(!1),i==="frame"&&t.dataset.inspectorProp==="title"&&L(),["shape","shapeType","diagramType"].includes(t.dataset.inspectorProp)&&q()}),r.inspector.addEventListener("change",a=>{const t=a.target.closest("[data-inspector-prop]"),i=t?.closest("[data-inspector-scope]")?.dataset.inspectorScope;if(!t||i!=="frame"||!["width","height"].includes(t.dataset.inspectorProp))return;const n=g();n&&(F(n,t.dataset.inspectorProp==="width"?Number(t.value):n.width,t.dataset.inspectorProp==="height"?Number(t.value):n.height),f(),m(!1))}),r.inspector.addEventListener("click",a=>{const t=a.target.closest("[data-frame-size]");if(t){const i=g(),[n,l]=t.dataset.frameSize.split("x").map(Number);F(i,n,l),f(),m(!1);return}if(a.target.closest("[data-focus-selected-frame]")){e.mode="focus",e.manualCamera=!1,m();return}if(a.target.closest("[data-delete-selected-element]")){const i=g();i.elements=i.elements.filter(n=>n.id!==e.selectedElementId),e.selectedElementId=null,f(),m();return}if(a.target.closest("[data-delete-selected-frame]")&&e.presentation.canvas.frames.length>1){const i=e.presentation.canvas.frames,n=i.findIndex(l=>l.id===e.selectedFrameId);i.splice(n,1),e.selectedFrameId=i[Math.max(0,n-1)]?.id||i[0]?.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,v(),f(),m()}});const B=()=>{e.presentation.title=r.title.value,e.presentation.description=r.description.value,e.presentation.backgroundColor=r.background.value,e.presentation.pathMode=r.pathMode.value,f(),document.activeElement===r.background&&m(!1)};[r.title,r.description,r.background,r.pathMode].forEach(a=>{a.addEventListener("input",B),a.addEventListener("change",B)}),r.viewport.addEventListener("wheel",a=>{a.preventDefault(),a.ctrlKey||a.metaKey?e.cameraScale=D(r.viewport,r.stage,V(a.deltaY),a.clientX,a.clientY,{minimumScale:.03,maximumScale:4}):e.cameraScale=G(r.stage,-a.deltaX,-a.deltaY),e.manualCamera=!0,I()},{passive:!1});const j=()=>{const a=e.drag;a&&(a.kind==="frame-resize"?(a.target.width=a.originWidth,a.target.height=a.originHeight,a.frameElements.forEach(t=>{t.item.x=t.x,t.item.y=t.y,t.item.width=t.width,t.item.height=t.height,t.item.type==="text"&&(t.item.fontSize=t.fontSize)})):a.target&&(a.target.x=a.originX,a.target.y=a.originY),e.drag=null,e.manualCamera=!0,m(!1))};C=O(r.viewport,r.stage,{minimumScale:.03,maximumScale:4,onStart:j,onUpdate:a=>{e.cameraScale=a,e.manualCamera=!0,I()},onTap:a=>{if(e.mode!=="overview"||e.drag?.moved)return!1;const t=a.target.closest?.("[data-frame-id]");return t?(Y(t.dataset.frameId),!0):!1}}),r.viewport.addEventListener("pointerdown",a=>{if(a.button!==0||C?.isActive())return;const t=a.target.closest("[data-frame-id]"),i=a.target.closest("[data-element-id]"),n=a.target.closest("[data-frame-resize]");if(!t)return;const l=A(e.presentation,t.dataset.frameId),s=K(l,i?.dataset.elementId);e.selectedFrameId=l.id,e.selectedElementId=e.mode==="focus"&&s?s.id:null;const p=(l.elements||[]).map(h=>({item:h,x:Number(h.x||0),y:Number(h.y||0),width:Number(h.width||100),height:Number(h.height||80),fontSize:Number(h.fontSize||32)}));e.drag={kind:e.mode==="overview"?n?"frame-resize":"frame":s?"element":null,target:e.mode==="overview"?l:s,node:e.mode==="overview"?t:i,startX:a.clientX,startY:a.clientY,originX:e.mode==="overview"?l.x:s?.x,originY:e.mode==="overview"?l.y:s?.y,originWidth:l.width,originHeight:l.height,frameElements:p,moved:!1},r.viewport.setPointerCapture(a.pointerId),q()}),r.viewport.addEventListener("pointermove",a=>{if(C?.isActive()||!e.drag?.kind||!e.drag.target)return;const t=(a.clientX-e.drag.startX)/e.cameraScale,i=(a.clientY-e.drag.startY)/e.cameraScale;if(Math.abs(t)+Math.abs(i)>2&&(e.drag.moved=!0),e.drag.kind==="frame-resize"){const n=k(e.drag.originWidth+t,320,1600,e.drag.originWidth),l=k(e.drag.originHeight+i,180,900,e.drag.originHeight),s=n/e.drag.originWidth,p=l/e.drag.originHeight,h=Math.min(s,p);e.drag.target.width=n,e.drag.target.height=l,e.drag.node.style.width=`${n}px`,e.drag.node.style.height=`${l}px`,e.drag.frameElements.forEach(u=>{u.item.x=u.x*s,u.item.y=u.y*p,u.item.width=Math.max(40,u.width*s),u.item.height=Math.max(30,u.height*p),u.item.type==="text"&&(u.item.fontSize=k(u.fontSize*h,10,160,u.fontSize));const y=e.drag.node.querySelector(`[data-element-id="${CSS.escape(u.item.id)}"]`);y&&(y.style.left=`${u.item.x}px`,y.style.top=`${u.item.y}px`,y.style.width=`${u.item.width}px`,y.style.height=`${u.item.height}px`,u.item.type==="text"&&(y.style.fontSize=`${u.item.fontSize}px`))})}else e.drag.target.x=Math.max(0,e.drag.originX+t),e.drag.target.y=Math.max(0,e.drag.originY+i),e.drag.node.style.left=`${e.drag.target.x}px`,e.drag.node.style.top=`${e.drag.target.y}px`});const R=a=>{if(C?.shouldSuppressTap()){e.drag=null;return}if(!e.drag)return;const t=e.drag;if(e.drag=null,r.viewport.hasPointerCapture(a.pointerId)&&r.viewport.releasePointerCapture(a.pointerId),t.moved)v(),f(),m(!1);else if(e.mode==="overview"&&t.kind==="frame"){e.mode="focus",e.manualCamera=!1,m();return}L()};r.viewport.addEventListener("pointerup",R),r.viewport.addEventListener("pointercancel",R);async function S(){if(window.clearTimeout(e.saveTimer),e.activeSave){const i=await e.activeSave;return i&&e.dirty?S():i}if(!e.dirty)return!0;const a=e.changeVersion;e.saving=!0,r.saveStatus.textContent="Menyimpan...",e.activeSave=(async()=>{try{v();const i=await fetch(o.dataset.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:JSON.stringify({title:r.title.value,description:r.description.value,background_color:r.background.value,path_mode:r.pathMode.value,canvas_data:e.presentation.canvas})}),n=await i.json();if(!i.ok)throw new Error(n.message||Object.values(n.errors||{})[0]?.[0]||"Presentasi gagal disimpan.");return e.changeVersion===a&&(e.dirty=!1,r.saveStatus.textContent="Semua perubahan tersimpan",r.saveStatus.classList.remove("text-amber-600","dark:text-amber-300","text-red-600")),!0}catch(i){return r.saveStatus.textContent=i.message,r.saveStatus.classList.add("text-red-600"),!1}finally{e.saving=!1}})();const t=await e.activeSave;return e.activeSave=null,t&&e.dirty?S():t}o.querySelector("[data-editor-save]").addEventListener("click",S),o.querySelectorAll("[data-export-link]").forEach(a=>{a.addEventListener("click",async t=>{if(!e.dirty)return;t.preventDefault(),await S()&&window.location.assign(a.href)})}),o.querySelectorAll("[data-save-before-open]").forEach(a=>{a.addEventListener("click",async t=>{if(!e.dirty)return;t.preventDefault();const i=a.target==="_blank"?window.open("about:blank","_blank"):null;await S()?i?i.location.href=a.href:window.location.assign(a.href):i&&i.close()})}),o.querySelectorAll("[data-publish-form]").forEach(a=>{a.addEventListener("submit",async t=>{e.dirty&&(t.preventDefault(),await S()&&a.submit())})}),window.addEventListener("beforeunload",a=>{e.dirty&&(a.preventDefault(),a.returnValue="")}),new ResizeObserver(()=>{e.manualCamera=!1,T(!1)}).observe(r.viewport),m(!1)}function x(c,e,r,C,z){return`<div><label class="form-label">${c}</label><input type="number" class="pkg-field w-full" min="${C}" max="${z}" data-inspector-prop="${e}" value="${Math.round(r)}"></div>`}function w(c,e,r){return`<div><label class="form-label">${c}</label><input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="${e}" value="${r}"></div>`}function d(c,e,r){return`<option value="${c}" ${c===r?"selected":""}>${e}</option>`}function M(c,e){return/^#[0-9a-f]{6}$/i.test(c||"")?c:e}function J(c){return{text:"Elemen Teks",image:"Elemen Gambar",logo:"Elemen Logo",youtube:"Elemen YouTube",link:"Elemen Tautan",shape:"Elemen Bentuk",diagram:"Elemen Diagram"}[c]||"Elemen"}function Z(c){return String(c||"").trim().match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/i)?.[1]||""}function P(c){return String(c??"").replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"})[e])}function E(c){return P(c).replace(/\n/g,"&#10;")}
