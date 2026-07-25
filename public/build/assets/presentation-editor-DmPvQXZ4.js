import{d as U,p as $,r as j,z as G,a as V,b as O,c as _,f as A,e as K,g as v,h as J}from"./presentation-canvas-DS94WncK.js";const d=document.getElementById("presentation-editor");if(d){const p=U(d.dataset.presentationPayload),e={presentation:p,selectedFrameId:p.canvas.frames[0]?.id||null,selectedElementId:null,mode:"overview",dirty:!1,saving:!1,changeVersion:0,saveTimer:null,activeSave:null,cameraScale:1,manualCamera:!1,drag:null},i={viewport:d.querySelector("[data-editor-viewport]"),stage:d.querySelector("[data-editor-stage]"),frameList:d.querySelector("[data-frame-list]"),inspector:d.querySelector("[data-editor-inspector]"),hint:d.querySelector("[data-editor-hint]"),saveStatus:d.querySelector("[data-save-status]"),title:d.querySelector("[data-editor-title]"),description:d.querySelector("[data-editor-description]"),background:d.querySelector("[data-editor-background]"),pathMode:d.querySelector("[data-editor-path-mode]"),imageInput:d.querySelector("[data-image-input]"),logoInput:d.querySelector("[data-logo-input]")};let C=null;i.title.value=p.title||"",i.description.value=p.description||"",i.background.value=p.backgroundColor||"#0f172a",i.pathMode.value=p.pathMode||"overview_between";const P=()=>{window.clearTimeout(e.saveTimer),e.saveTimer=window.setTimeout(()=>S(),1200)},f=()=>{e.dirty=!0,e.changeVersion+=1,i.saveStatus.textContent="Belum disimpan · akan disimpan otomatis",i.saveStatus.classList.add("text-amber-600","dark:text-amber-300"),P()},h=()=>A(e.presentation,e.selectedFrameId),F=()=>K(h(),e.selectedElementId),k=()=>{const a=e.presentation.canvas.frames,t=a.reduce((r,l)=>Math.max(r,Number(l.x||0)+Number(l.width||0)+120),1200),n=a.reduce((r,l)=>Math.max(r,Number(l.y||0)+Number(l.height||0)+120),800);e.presentation.canvas.width=v(t,1200,7e3,2400),e.presentation.canvas.height=v(n,800,12500,1400)},H=(a,t,n)=>{const r=Math.max(1,Number(a.width||800)),l=Math.max(1,Number(a.height||450)),o=v(t,320,1600,r),m=v(n,180,900,l),u=o/r,s=m/l,b=Math.min(u,s);(a.elements||[]).forEach(y=>{y.x=Number(y.x||0)*u,y.y=Number(y.y||0)*s,y.width=Math.max(40,Number(y.width||100)*u),y.height=Math.max(30,Number(y.height||80)*s),y.type==="text"&&(y.fontSize=v(Number(y.fontSize||32)*b,10,160,32))}),a.width=o,a.height=m,k()};k();const T=()=>{const a=e.mode==="focus"?h():null,t=a?`Fokus: ${a.title}`:"Mode Overview";i.hint.textContent=`${t} · ${Math.round(e.cameraScale*100)}%`},L=(a=!0)=>{const t=e.mode==="focus"?h():null;e.cameraScale=J(i.viewport,i.stage,e.presentation.canvas,t,a),e.manualCamera=!1,T()},g=(a=!0)=>{j({stage:i.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),M(),q(),requestAnimationFrame(()=>{if(e.manualCamera){T();return}L(a)})},M=()=>{i.frameList.replaceChildren(),e.presentation.canvas.frames.forEach((a,t)=>{const n=document.createElement("div");n.className=`pkg-presentation-frame-list-item${a.id===e.selectedFrameId?" is-selected":""}`;const r=document.createElement("button");r.type="button",r.className="min-w-0 flex-1 text-left",r.dataset.frameFocus=a.id,r.innerHTML=`<span class="block text-xs font-bold text-emerald-600">${t+1}</span>`;const l=document.createElement("span");l.className="block truncate text-sm font-semibold text-gray-900 dark:text-white",l.textContent=a.title,r.appendChild(l),n.appendChild(r);const o=document.createElement("div");o.className="flex gap-1",o.innerHTML=`
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="up" data-frame-id="${a.id}" aria-label="Naik">↑</button>
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="down" data-frame-id="${a.id}" aria-label="Turun">↓</button>
            `,n.appendChild(o),i.frameList.appendChild(n)})},Y=(a,t=!1)=>`
        <div class="grid grid-cols-2 gap-3">
            ${x("X","x",a.x,0,5e3)}
            ${x("Y","y",a.y,0,t?11e3:1100)}
            ${x("Lebar","width",a.width,t?320:40,1600)}
            ${x("Tinggi","height",a.height,t?180:30,900)}
        </div>
    `,q=()=>{const a=h(),t=F();if(!a){i.inspector.innerHTML='<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';return}if(!t){i.inspector.innerHTML=`
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
                            ${c("rounded","Sudut membulat",a.shape||"rounded")}
                            ${c("rectangle","Kotak",a.shape)}
                            ${c("circle","Lingkaran / oval",a.shape)}
                            ${c("hexagon","Segi enam",a.shape)}
                            ${c("custom","Radius buatan sendiri",a.shape)}
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
                    ${Y(a,!0)}
                    <button type="button" class="btn-primary w-full justify-center" data-focus-selected-frame>Fokuskan Frame</button>
                    <button type="button" class="btn-danger w-full justify-center" data-delete-selected-frame ${e.presentation.canvas.frames.length<=1?"disabled":""}>Hapus Frame</button>
                </div>
            `;return}let n="";t.type==="text"?n=`
                <div>
                    <label class="form-label">Isi teks</label>
                    <textarea class="pkg-field w-full" rows="5" maxlength="5000" data-inspector-prop="text">${N(t.text||"")}</textarea>
                </div>
                ${x("Ukuran huruf","fontSize",t.fontSize||32,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna teks","color",t.color||"#0f172a")}
                    ${w("Latar","backgroundColor",z(t.backgroundColor,"#ffffff"))}
                </div>
                <div>
                    <label class="form-label">Perataan</label>
                    <select class="pkg-field w-full" data-inspector-prop="align">
                        ${c("left","Kiri",t.align)}
                        ${c("center","Tengah",t.align)}
                        ${c("right","Kanan",t.align)}
                    </select>
                </div>
                <label class="pkg-check"><input type="checkbox" data-inspector-prop="bold" ${t.bold?"checked":""}><span>Teks tebal</span></label>
            `:t.type==="image"||t.type==="logo"?n=`
                <div>
                    <label class="form-label">Teks alternatif</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="alt" value="${E(t.alt||"")}">
                </div>
                <div>
                    <label class="form-label">Penyesuaian gambar</label>
                    <select class="pkg-field w-full" data-inspector-prop="fit">
                        ${c("cover","Penuhi area",t.fit)}
                        ${c("contain","Tampilkan utuh",t.fit)}
                    </select>
                </div>
                ${t.type==="logo"?`
                    <div>
                        <label class="form-label">Bentuk logo</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${c("circle","Lingkaran",t.shape||"circle")}
                            ${c("rounded","Sudut membulat",t.shape)}
                            ${c("square","Kotak",t.shape)}
                            ${c("hexagon","Segi enam",t.shape)}
                        </select>
                    </div>
                `:""}
            `:t.type==="youtube"?n=`
                <div>
                    <label class="form-label">Link YouTube</label>
                    <input type="url" class="pkg-field w-full" maxlength="500" data-inspector-prop="youtubeUrl" value="${E(t.youtubeUrl||"")}" placeholder="https://youtu.be/...">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Video dapat diputar dan dibuka layar penuh pada Pratinjau atau Tautan Publik.</p>
                </div>
                <div>
                    <label class="form-label">Judul video</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="title" value="${E(t.title||"")}">
                </div>
            `:t.type==="link"?n=`
                <div><label class="form-label">Label tautan</label><input class="pkg-field w-full" maxlength="160" data-inspector-prop="text" value="${E(t.text||"")}"></div>
                <div><label class="form-label">Alamat tautan</label><input type="url" class="pkg-field w-full" maxlength="1000" data-inspector-prop="url" value="${E(t.url||"")}" placeholder="https://..."></div>
                <div>
                    <label class="form-label">Tampilan tautan</label>
                    <select class="pkg-field w-full" data-inspector-prop="linkStyle">
                        ${c("button","Tombol",t.linkStyle)}
                        ${c("card","Kartu",t.linkStyle)}
                        ${c("text","Teks",t.linkStyle)}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna teks","color",t.color||"#ffffff")}
                    ${w("Latar","backgroundColor",z(t.backgroundColor,"#047857"))}
                </div>
            `:t.type==="shape"?n=`
                <div><label class="form-label">Teks di bentuk</label><textarea class="pkg-field w-full" rows="3" maxlength="1000" data-inspector-prop="text">${N(t.text||"")}</textarea></div>
                <div>
                    <label class="form-label">Bentuk</label>
                    <select class="pkg-field w-full" data-inspector-prop="shapeType">
                        ${c("circle","Lingkaran / oval",t.shapeType)}
                        ${c("rounded","Sudut membulat",t.shapeType)}
                        ${c("rectangle","Kotak",t.shapeType)}
                        ${c("hexagon","Segi enam",t.shapeType)}
                        ${c("custom","Radius buatan sendiri",t.shapeType)}
                    </select>
                </div>
                ${t.shapeType==="custom"?x("Radius sudut","borderRadius",t.borderRadius||24,0,240):""}
                ${x("Ukuran huruf","fontSize",t.fontSize||28,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna teks","color",t.color||"#ffffff")}
                    ${w("Warna bentuk","backgroundColor",z(t.backgroundColor,"#0f766e"))}
                </div>
            `:n=`
                <div>
                    <label class="form-label">Isi diagram (satu baris per kotak)</label>
                    <textarea class="pkg-field w-full" rows="6" data-inspector-prop="items">${N((t.items||[]).join(`
`))}</textarea>
                </div>
                <div>
                    <label class="form-label">Bentuk alur</label>
                    <select class="pkg-field w-full" data-inspector-prop="diagramType">
                        ${c("process","Proses mendatar",t.diagramType)}
                        ${c("cycle","Siklus",t.diagramType)}
                        ${c("hierarchy","Hierarki",t.diagramType)}
                        ${c("radial","Radial dengan pusat",t.diagramType)}
                    </select>
                </div>
                ${t.diagramType==="radial"?`
                    <div><label class="form-label">Teks pusat / logo</label><input class="pkg-field w-full" maxlength="120" data-inspector-prop="centerText" value="${E(t.centerText||"")}"></div>
                    <div>
                        <label class="form-label">Bentuk node</label>
                        <select class="pkg-field w-full" data-inspector-prop="nodeShape">
                            ${c("circle","Lingkaran",t.nodeShape||"circle")}
                            ${c("rounded","Sudut membulat",t.nodeShape)}
                            ${c("square","Kotak",t.nodeShape)}
                            ${c("hexagon","Segi enam",t.nodeShape)}
                        </select>
                    </div>
                `:""}
                <div class="grid grid-cols-2 gap-3">
                    ${w("Warna diagram","color",t.color||"#0f172a")}
                    ${w("Latar","backgroundColor",z(t.backgroundColor,"#ffffff"))}
                </div>
            `,i.inspector.innerHTML=`
            <div class="space-y-4" data-inspector-scope="element">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${Z(t.type)}
                </div>
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Seret bagian tengah elemen untuk memindahkan. Gunakan penanda hijau pada sisi dan sudut untuk mengubah ukuran langsung.</p>
                ${n}
                ${Y(t)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `},X=a=>{e.selectedFrameId=a,e.selectedElementId=null,e.mode="focus",e.manualCamera=!1,g()};d.querySelector("[data-editor-overview]").addEventListener("click",()=>{e.mode="overview",e.selectedElementId=null,e.manualCamera=!1,g()}),d.querySelector("[data-editor-fit]").addEventListener("click",()=>{e.manualCamera=!1,L()}),d.querySelector("[data-add-frame]").addEventListener("click",()=>{const a=e.presentation.canvas.frames.length,t=180+a%2*1100,n=180+Math.floor(a/2)*560,r={id:$("frame"),title:`Frame ${a+1}`,x:t,y:n,width:800,height:450,backgroundColor:"#ffffff",shape:"rounded",borderRadius:22,elements:[]};e.presentation.canvas.frames.push(r),k(),e.selectedFrameId=r.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,f(),g()}),d.querySelector("[data-arrange-frames]").addEventListener("click",()=>{const a=e.presentation.canvas.frames,t=Math.max(...a.map(o=>Number(o.width||800))),n=Math.max(...a.map(o=>Number(o.height||450))),r=160,l=140;a.forEach((o,m)=>{o.x=120+m%2*(t+r),o.y=120+Math.floor(m/2)*(n+l)}),e.mode="overview",e.selectedElementId=null,e.manualCamera=!1,k(),f(),g()}),d.querySelector("[data-add-text]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:$("element"),type:"text",x:70,y:80,width:Math.max(240,a.width-140),height:130,rotation:0,text:"Tulis materi di sini",fontSize:36,color:"#0f172a",backgroundColor:"transparent",align:"left",bold:!1};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),g()}),d.querySelector("[data-add-diagram]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:$("element"),type:"diagram",x:70,y:130,width:Math.max(360,a.width-140),height:180,rotation:0,color:"#047857",backgroundColor:"transparent",diagramType:"process",items:["Pembuka","Pembahasan","Kesimpulan"]};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),g()}),d.querySelector("[data-add-youtube]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:$("element"),type:"youtube",x:90,y:90,width:Math.min(560,a.width-180),height:Math.min(315,a.height-150),rotation:0,youtubeUrl:"",youtubeId:"",title:"Video YouTube",color:"#ffffff",backgroundColor:"#0f172a"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),g()}),d.querySelector("[data-add-link]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:$("element"),type:"link",x:90,y:300,width:260,height:70,rotation:0,text:"Buka tautan",url:"https://",linkStyle:"button",color:"#ffffff",backgroundColor:"#047857"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),g()}),d.querySelector("[data-add-shape]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:$("element"),type:"shape",x:110,y:140,width:220,height:150,rotation:0,text:"Isi bentuk",shapeType:"rounded",borderRadius:24,fontSize:28,color:"#ffffff",backgroundColor:"#0f766e"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,f(),g()}),d.querySelector("[data-add-image]").addEventListener("click",()=>{h()&&i.imageInput.click()}),d.querySelector("[data-add-logo]").addEventListener("click",()=>{h()&&i.logoInput.click()});const W=async(a,t)=>{const n=a.files?.[0],r=h();if(!n||!r)return;const l=new FormData;l.append("image",n),i.saveStatus.textContent="Mengunggah gambar...";try{const o=await fetch(d.dataset.uploadUrl,{method:"POST",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:l}),m=await o.json();if(!o.ok)throw new Error(m.message||"Gambar gagal diunggah.");e.presentation.assets[String(m.asset.id)]=m.asset;const u={id:$("element"),type:t,assetId:m.asset.id,x:90,y:80,width:Math.min(t==="logo"?220:420,r.width-180),height:Math.min(t==="logo"?220:280,r.height-140),rotation:0,alt:m.asset.name,fit:t==="logo"?"contain":"cover",shape:t==="logo"?"circle":"rounded",color:"#0f172a",backgroundColor:"transparent"};r.elements.push(u),e.selectedElementId=u.id,e.mode="focus",e.manualCamera=!1,f(),g()}catch(o){i.saveStatus.textContent=o.message,i.saveStatus.classList.add("text-red-600")}finally{a.value=""}};i.imageInput.addEventListener("change",()=>W(i.imageInput,"image")),i.logoInput.addEventListener("change",()=>W(i.logoInput,"logo")),i.frameList.addEventListener("click",a=>{const t=a.target.closest("[data-frame-focus]");if(t){X(t.dataset.frameFocus);return}const n=a.target.closest("[data-frame-move]");if(!n)return;const r=e.presentation.canvas.frames,l=r.findIndex(m=>m.id===n.dataset.frameId),o=n.dataset.frameMove==="up"?l-1:l+1;l<0||o<0||o>=r.length||([r[l],r[o]]=[r[o],r[l]],f(),M())}),i.inspector.addEventListener("input",a=>{const t=a.target.closest("[data-inspector-prop]");if(!t)return;const n=t.closest("[data-inspector-scope]")?.dataset.inspectorScope,r=n==="frame"?h():F();if(!r||n==="frame"&&["width","height"].includes(t.dataset.inspectorProp))return;let l=t.type==="checkbox"?t.checked:t.value;["x","y","width","height","fontSize","borderRadius"].includes(t.dataset.inspectorProp)&&(l=Number(l)),t.dataset.inspectorProp==="items"&&(l=String(l).split(`
`).map(o=>o.trim()).filter(Boolean).slice(0,8)),r[t.dataset.inspectorProp]=l,t.dataset.inspectorProp==="youtubeUrl"&&(r.youtubeId=Q(l)),n==="frame"&&k(),f(),j({stage:i.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),e.manualCamera?T():L(!1),n==="frame"&&t.dataset.inspectorProp==="title"&&M(),["shape","shapeType","diagramType"].includes(t.dataset.inspectorProp)&&q()}),i.inspector.addEventListener("change",a=>{const t=a.target.closest("[data-inspector-prop]"),n=t?.closest("[data-inspector-scope]")?.dataset.inspectorScope;if(!t||n!=="frame"||!["width","height"].includes(t.dataset.inspectorProp))return;const r=h();r&&(H(r,t.dataset.inspectorProp==="width"?Number(t.value):r.width,t.dataset.inspectorProp==="height"?Number(t.value):r.height),f(),g(!1))}),i.inspector.addEventListener("click",a=>{const t=a.target.closest("[data-frame-size]");if(t){const n=h(),[r,l]=t.dataset.frameSize.split("x").map(Number);H(n,r,l),f(),g(!1);return}if(a.target.closest("[data-focus-selected-frame]")){e.mode="focus",e.manualCamera=!1,g();return}if(a.target.closest("[data-delete-selected-element]")){const n=h();n.elements=n.elements.filter(r=>r.id!==e.selectedElementId),e.selectedElementId=null,f(),g();return}if(a.target.closest("[data-delete-selected-frame]")&&e.presentation.canvas.frames.length>1){const n=e.presentation.canvas.frames,r=n.findIndex(l=>l.id===e.selectedFrameId);n.splice(r,1),e.selectedFrameId=n[Math.max(0,r-1)]?.id||n[0]?.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,k(),f(),g()}});const R=()=>{e.presentation.title=i.title.value,e.presentation.description=i.description.value,e.presentation.backgroundColor=i.background.value,e.presentation.pathMode=i.pathMode.value,f(),document.activeElement===i.background&&g(!1)};[i.title,i.description,i.background,i.pathMode].forEach(a=>{a.addEventListener("input",R),a.addEventListener("change",R)}),i.viewport.addEventListener("wheel",a=>{a.preventDefault(),a.ctrlKey||a.metaKey?e.cameraScale=G(i.viewport,i.stage,V(a.deltaY),a.clientX,a.clientY,{minimumScale:.03,maximumScale:4}):e.cameraScale=O(i.stage,-a.deltaX,-a.deltaY),e.manualCamera=!0,T()},{passive:!1});const D=()=>{const a=e.drag;a&&(a.kind==="frame-resize"?(a.target.width=a.originWidth,a.target.height=a.originHeight,a.frameElements.forEach(t=>{t.item.x=t.x,t.item.y=t.y,t.item.width=t.width,t.item.height=t.height,t.item.type==="text"&&(t.item.fontSize=t.fontSize)})):a.kind==="element-resize"&&a.target?(a.target.x=a.originX,a.target.y=a.originY,a.target.width=a.originWidth,a.target.height=a.originHeight):a.target&&(a.target.x=a.originX,a.target.y=a.originY),e.drag=null,e.manualCamera=!0,g(!1))};C=_(i.viewport,i.stage,{minimumScale:.03,maximumScale:4,onStart:D,onUpdate:a=>{e.cameraScale=a,e.manualCamera=!0,T()},onTap:a=>{if(e.mode!=="overview"||e.drag?.moved)return!1;const t=a.target.closest?.("[data-frame-id]");return t?(X(t.dataset.frameId),!0):!1}}),i.viewport.addEventListener("pointerdown",a=>{if(a.button!==0||C?.isActive())return;const t=a.target.closest("[data-frame-id]"),n=a.target.closest("[data-frame-resize]"),r=a.target.closest("[data-element-resize]");if(!t)return;const l=A(e.presentation,t.dataset.frameId),o=a.target.closest(".pkg-presentation-element[data-element-id]"),m=r?.dataset.elementId||o?.dataset.elementId,u=K(l,m),s=u?t.querySelector(`.pkg-presentation-element[data-element-id="${CSS.escape(u.id)}"]`):null,b=r?.closest(".pkg-presentation-element-controls")||(u?t.querySelector(`.pkg-presentation-element-controls[data-element-id="${CSS.escape(u.id)}"]`):null);e.selectedFrameId=l.id,e.selectedElementId=e.mode==="focus"&&u?u.id:null;const y=(l.elements||[]).map(I=>({item:I,x:Number(I.x||0),y:Number(I.y||0),width:Number(I.width||100),height:Number(I.height||80),fontSize:Number(I.fontSize||32)}));e.drag={kind:e.mode==="overview"?n?"frame-resize":"frame":r?"element-resize":u?"element":null,target:e.mode==="overview"?l:u,node:e.mode==="overview"?t:s,controlsNode:b,frame:l,resizeDirection:r?.dataset.elementResize||null,startX:a.clientX,startY:a.clientY,originX:e.mode==="overview"?l.x:u?.x,originY:e.mode==="overview"?l.y:u?.y,originWidth:e.mode==="overview"?l.width:u?.width,originHeight:e.mode==="overview"?l.height:u?.height,frameElements:y,moved:!1},i.viewport.setPointerCapture(a.pointerId),q()}),i.viewport.addEventListener("pointermove",a=>{if(C?.isActive()||!e.drag?.kind||!e.drag.target)return;const t=(a.clientX-e.drag.startX)/e.cameraScale,n=(a.clientY-e.drag.startY)/e.cameraScale;if(Math.abs(t)+Math.abs(n)>2&&(e.drag.moved=!0),e.drag.kind==="frame-resize"){const r=v(e.drag.originWidth+t,320,1600,e.drag.originWidth),l=v(e.drag.originHeight+n,180,900,e.drag.originHeight),o=r/e.drag.originWidth,m=l/e.drag.originHeight,u=Math.min(o,m);e.drag.target.width=r,e.drag.target.height=l,e.drag.node.style.width=`${r}px`,e.drag.node.style.height=`${l}px`,e.drag.frameElements.forEach(s=>{s.item.x=s.x*o,s.item.y=s.y*m,s.item.width=Math.max(40,s.width*o),s.item.height=Math.max(30,s.height*m),s.item.type==="text"&&(s.item.fontSize=v(s.fontSize*u,10,160,s.fontSize));const b=e.drag.node.querySelector(`[data-element-id="${CSS.escape(s.item.id)}"]`);b&&(b.style.left=`${s.item.x}px`,b.style.top=`${s.item.y}px`,b.style.width=`${s.item.width}px`,b.style.height=`${s.item.height}px`,s.item.type==="text"&&(b.style.fontSize=`${s.item.fontSize}px`))})}else if(e.drag.kind==="element-resize"){const r=e.drag.resizeDirection||"se",l=40,o=30;let m=e.drag.originX,u=e.drag.originY,s=e.drag.originWidth,b=e.drag.originHeight;r.includes("e")&&(s=v(e.drag.originWidth+t,l,Math.max(l,e.drag.frame.width-e.drag.originX),e.drag.originWidth)),r.includes("s")&&(b=v(e.drag.originHeight+n,o,Math.max(o,e.drag.frame.height-e.drag.originY),e.drag.originHeight)),r.includes("w")&&(m=v(e.drag.originX+t,0,e.drag.originX+e.drag.originWidth-l,e.drag.originX),s=e.drag.originWidth+(e.drag.originX-m)),r.includes("n")&&(u=v(e.drag.originY+n,0,e.drag.originY+e.drag.originHeight-o,e.drag.originY),b=e.drag.originHeight+(e.drag.originY-u)),Object.assign(e.drag.target,{x:m,y:u,width:s,height:b}),e.drag.node.style.left=`${m}px`,e.drag.node.style.top=`${u}px`,e.drag.node.style.width=`${s}px`,e.drag.node.style.height=`${b}px`,e.drag.controlsNode&&(e.drag.controlsNode.style.left=`${m}px`,e.drag.controlsNode.style.top=`${u}px`,e.drag.controlsNode.style.width=`${s}px`,e.drag.controlsNode.style.height=`${b}px`)}else{const r=Math.max(0,e.drag.frame.width-e.drag.target.width),l=Math.max(0,e.drag.frame.height-e.drag.target.height);e.drag.target.x=v(e.drag.originX+t,0,r,e.drag.originX),e.drag.target.y=v(e.drag.originY+n,0,l,e.drag.originY),e.drag.node.style.left=`${e.drag.target.x}px`,e.drag.node.style.top=`${e.drag.target.y}px`,e.drag.controlsNode&&(e.drag.controlsNode.style.left=`${e.drag.target.x}px`,e.drag.controlsNode.style.top=`${e.drag.target.y}px`)}});const B=a=>{if(C?.shouldSuppressTap()){e.drag=null;return}if(!e.drag)return;const t=e.drag;if(e.drag=null,i.viewport.hasPointerCapture(a.pointerId)&&i.viewport.releasePointerCapture(a.pointerId),t.moved)k(),f(),g(!1);else if(e.mode==="overview"&&t.kind==="frame"){e.mode="focus",e.manualCamera=!1,g();return}else if(e.mode==="focus"&&t.kind==="element"){g(!1);return}M()};i.viewport.addEventListener("pointerup",B),i.viewport.addEventListener("pointercancel",B);async function S(){if(window.clearTimeout(e.saveTimer),e.activeSave){const n=await e.activeSave;return n&&e.dirty?S():n}if(!e.dirty)return!0;const a=e.changeVersion;e.saving=!0,i.saveStatus.textContent="Menyimpan...",e.activeSave=(async()=>{try{k();const n=await fetch(d.dataset.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:JSON.stringify({title:i.title.value,description:i.description.value,background_color:i.background.value,path_mode:i.pathMode.value,canvas_data:e.presentation.canvas})}),r=await n.json();if(!n.ok)throw new Error(r.message||Object.values(r.errors||{})[0]?.[0]||"Presentasi gagal disimpan.");return e.changeVersion===a&&(e.dirty=!1,i.saveStatus.textContent="Semua perubahan tersimpan",i.saveStatus.classList.remove("text-amber-600","dark:text-amber-300","text-red-600")),!0}catch(n){return i.saveStatus.textContent=n.message,i.saveStatus.classList.add("text-red-600"),!1}finally{e.saving=!1}})();const t=await e.activeSave;return e.activeSave=null,t&&e.dirty?S():t}d.querySelector("[data-editor-save]").addEventListener("click",S),d.querySelectorAll("[data-export-link]").forEach(a=>{a.addEventListener("click",async t=>{if(!e.dirty)return;t.preventDefault(),await S()&&window.location.assign(a.href)})}),d.querySelectorAll("[data-save-before-open]").forEach(a=>{a.addEventListener("click",async t=>{if(!e.dirty)return;t.preventDefault();const n=a.target==="_blank"?window.open("about:blank","_blank"):null;await S()?n?n.location.href=a.href:window.location.assign(a.href):n&&n.close()})}),d.querySelectorAll("[data-publish-form]").forEach(a=>{a.addEventListener("submit",async t=>{e.dirty&&(t.preventDefault(),await S()&&a.submit())})}),window.addEventListener("beforeunload",a=>{e.dirty&&(a.preventDefault(),a.returnValue="")}),new ResizeObserver(()=>{e.manualCamera=!1,L(!1)}).observe(i.viewport),g(!1)}function x(p,e,i,C,P){return`<div><label class="form-label">${p}</label><input type="number" class="pkg-field w-full" min="${C}" max="${P}" data-inspector-prop="${e}" value="${Math.round(i)}"></div>`}function w(p,e,i){return`<div><label class="form-label">${p}</label><input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="${e}" value="${i}"></div>`}function c(p,e,i){return`<option value="${p}" ${p===i?"selected":""}>${e}</option>`}function z(p,e){return/^#[0-9a-f]{6}$/i.test(p||"")?p:e}function Z(p){return{text:"Elemen Teks",image:"Elemen Gambar",logo:"Elemen Logo",youtube:"Elemen YouTube",link:"Elemen Tautan",shape:"Elemen Bentuk",diagram:"Elemen Diagram"}[p]||"Elemen"}function Q(p){return String(p||"").trim().match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/i)?.[1]||""}function N(p){return String(p??"").replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"})[e])}function E(p){return N(p).replace(/\n/g,"&#10;")}
