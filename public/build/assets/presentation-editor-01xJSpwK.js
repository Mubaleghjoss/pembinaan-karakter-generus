import{d as Q,p as E,r as _,z as ee,a as te,b as ae,c as re,s as ie,f as W,e as X,g as b,h as ne}from"./presentation-canvas-B_WsLiEy.js";const l=document.getElementById("presentation-editor");if(l){const f=Q(l.dataset.presentationPayload),e={presentation:f,selectedFrameId:f.canvas.frames[0]?.id||null,selectedElementId:null,mode:"overview",dirty:!1,saving:!1,changeVersion:0,saveTimer:null,activeSave:null,cameraScale:1,focusScale:1,overviewCandidate:!1,manualCamera:!1,drag:null,history:{current:null,undo:[],redo:[],lastGroup:null,lastRecordedAt:0}},r={viewport:l.querySelector("[data-editor-viewport]"),stage:l.querySelector("[data-editor-stage]"),frameList:l.querySelector("[data-frame-list]"),inspector:l.querySelector("[data-editor-inspector]"),hint:l.querySelector("[data-editor-hint]"),saveStatus:l.querySelector("[data-save-status]"),title:l.querySelector("[data-editor-title]"),description:l.querySelector("[data-editor-description]"),background:l.querySelector("[data-editor-background]"),pathMode:l.querySelector("[data-editor-path-mode]"),imageInput:l.querySelector("[data-image-input]"),logoInput:l.querySelector("[data-logo-input]"),undo:l.querySelector("[data-editor-undo]"),redo:l.querySelector("[data-editor-redo]")};let T=null;r.title.value=f.title||"",r.description.value=f.description||"",r.background.value=f.backgroundColor||"#0f172a",r.pathMode.value=f.pathMode||"overview_between";const M=()=>{window.clearTimeout(e.saveTimer),e.saveTimer=window.setTimeout(()=>C(),1200)},R=()=>JSON.stringify({canvas:e.presentation.canvas,title:e.presentation.title,description:e.presentation.description,backgroundColor:e.presentation.backgroundColor,pathMode:e.presentation.pathMode}),z=()=>{r.undo.disabled=e.history.undo.length===0,r.redo.disabled=e.history.redo.length===0},J=(a=null)=>{const t=R();if(e.history.current===null){e.history.current=t,z();return}if(t===e.history.current)return;const i=performance.now();a&&e.history.lastGroup===a&&i-e.history.lastRecordedAt<700||(e.history.undo.push(e.history.current),e.history.undo.length>80&&e.history.undo.shift()),e.history.current=t,e.history.redo=[],e.history.lastGroup=a,e.history.lastRecordedAt=i,z()},v=(a=null)=>{J(a),e.dirty=!0,e.changeVersion+=1,r.saveStatus.textContent="Belum disimpan · akan disimpan otomatis",r.saveStatus.classList.add("text-amber-600","dark:text-amber-300"),M()},h=()=>W(e.presentation,e.selectedFrameId),K=()=>X(h(),e.selectedElementId),S=()=>{const a=e.presentation.canvas.frames,t=a.reduce((n,o)=>Math.max(n,Number(o.x||0)+Number(o.width||0)+120),1200),i=a.reduce((n,o)=>Math.max(n,Number(o.y||0)+Number(o.height||0)+120),800);e.presentation.canvas.width=b(t,1200,7e3,2400),e.presentation.canvas.height=b(i,800,12500,1400)},A=(a,t,i)=>{const n=Math.max(1,Number(a.width||800)),o=Math.max(1,Number(a.height||450)),d=b(t,320,1600,n),m=b(i,180,900,o),u=d/n,y=m/o,k=Math.min(u,y);(a.elements||[]).forEach(g=>{g.x=Number(g.x||0)*u,g.y=Number(g.y||0)*y,g.width=Math.max(40,Number(g.width||100)*u),g.height=Math.max(30,Number(g.height||80)*y),g.type==="text"&&(g.fontSize=b(Number(g.fontSize||32)*k,10,160,32))}),a.width=d,a.height=m,S()};S(),e.history.current=R(),z();const L=()=>{const a=e.mode==="focus"?h():null,t=a?`Fokus: ${a.title}`:"Mode Overview";r.hint.textContent=`${t} · ${Math.round(e.cameraScale*100)}%`},P=(a=!0)=>{const t=e.mode==="focus"?h():null;e.cameraScale=ne(r.viewport,r.stage,e.presentation.canvas,t,a),t&&(e.focusScale=e.cameraScale),e.manualCamera=!1,L()},p=(a=!0)=>{_({stage:r.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),N(),H(),requestAnimationFrame(()=>{if(e.manualCamera){L();return}P(a)})},N=()=>{r.frameList.replaceChildren(),e.presentation.canvas.frames.forEach((a,t)=>{const i=document.createElement("div");i.className=`pkg-presentation-frame-list-item${a.id===e.selectedFrameId?" is-selected":""}`;const n=document.createElement("button");n.type="button",n.className="min-w-0 flex-1 text-left",n.dataset.frameFocus=a.id,n.innerHTML=`<span class="block text-xs font-bold text-emerald-600">${t+1}</span>`;const o=document.createElement("span");o.className="block truncate text-sm font-semibold text-gray-900 dark:text-white",o.textContent=a.title,n.appendChild(o),i.appendChild(n);const d=document.createElement("div");d.className="flex gap-1",d.innerHTML=`
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="up" data-frame-id="${a.id}" aria-label="Naik">↑</button>
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="down" data-frame-id="${a.id}" aria-label="Turun">↓</button>
            `,i.appendChild(d),r.frameList.appendChild(i)})},B=(a,t=!1)=>`
        <div class="grid grid-cols-2 gap-3">
            ${w("X","x",a.x,0,5e3)}
            ${w("Y","y",a.y,0,t?11e3:1100)}
            ${w("Lebar","width",a.width,t?320:40,1600)}
            ${w("Tinggi","height",a.height,t?180:30,900)}
        </div>
    `,H=()=>{const a=h(),t=K();if(!a){r.inspector.innerHTML='<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';return}if(!t){r.inspector.innerHTML=`
                <div class="space-y-4" data-inspector-scope="frame">
                    <div>
                        <label class="form-label">Judul frame</label>
                        <input class="pkg-field w-full" maxlength="120" data-inspector-prop="title" value="${I(a.title)}">
                    </div>
                    <div>
                        <label class="form-label">Warna frame</label>
                        <input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="backgroundColor" value="${a.backgroundColor||"#ffffff"}">
                    </div>
                    <div>
                        <label class="form-label">Bentuk frame</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${s("rounded","Sudut membulat",a.shape||"rounded")}
                            ${s("rectangle","Kotak",a.shape)}
                            ${s("circle","Lingkaran / oval",a.shape)}
                            ${s("hexagon","Segi enam",a.shape)}
                            ${s("custom","Radius buatan sendiri",a.shape)}
                        </select>
                    </div>
                    ${a.shape==="custom"?w("Radius sudut","borderRadius",a.borderRadius||22,0,240):""}
                    <div>
                        <label class="form-label">Ukuran frame</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="pkg-frame-size-button" data-frame-size="560x315">Kecil</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="800x450">Sedang</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="1120x630">Besar</button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Gunakan preset, isi ukuran manual, atau seret pegangan hijau di sudut kanan bawah frame.</p>
                    </div>
                    ${B(a,!0)}
                    <button type="button" class="btn-primary w-full justify-center" data-focus-selected-frame>Fokuskan Frame</button>
                    <button type="button" class="btn-danger w-full justify-center" data-delete-selected-frame ${e.presentation.canvas.frames.length<=1?"disabled":""}>Hapus Frame</button>
                </div>
            `;return}let i="";t.type==="text"?i=`
                <div>
                    <label class="form-label">Isi teks</label>
                    <textarea class="pkg-field w-full" rows="5" maxlength="5000" data-inspector-prop="text">${F(t.text||"")}</textarea>
                </div>
                ${w("Ukuran huruf","fontSize",t.fontSize||32,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${$("Warna teks","color",t.color||"#0f172a")}
                    ${$("Latar","backgroundColor",q(t.backgroundColor,"#ffffff"))}
                </div>
                <div>
                    <label class="form-label">Perataan</label>
                    <select class="pkg-field w-full" data-inspector-prop="align">
                        ${s("left","Kiri",t.align)}
                        ${s("center","Tengah",t.align)}
                        ${s("right","Kanan",t.align)}
                    </select>
                </div>
                <label class="pkg-check"><input type="checkbox" data-inspector-prop="bold" ${t.bold?"checked":""}><span>Teks tebal</span></label>
            `:t.type==="image"||t.type==="logo"?i=`
                <div>
                    <label class="form-label">Teks alternatif</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="alt" value="${I(t.alt||"")}">
                </div>
                <div>
                    <label class="form-label">Penyesuaian gambar</label>
                    <select class="pkg-field w-full" data-inspector-prop="fit">
                        ${s("cover","Penuhi area",t.fit)}
                        ${s("contain","Tampilkan utuh",t.fit)}
                    </select>
                </div>
                ${t.type==="logo"?`
                    <div>
                        <label class="form-label">Bentuk logo</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${s("circle","Lingkaran",t.shape||"circle")}
                            ${s("rounded","Sudut membulat",t.shape)}
                            ${s("square","Kotak",t.shape)}
                            ${s("hexagon","Segi enam",t.shape)}
                        </select>
                    </div>
                `:""}
            `:t.type==="youtube"?i=`
                <div>
                    <label class="form-label">Link YouTube</label>
                    <input type="url" class="pkg-field w-full" maxlength="500" data-inspector-prop="youtubeUrl" value="${I(t.youtubeUrl||"")}" placeholder="https://youtu.be/...">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Video dapat diputar dan dibuka layar penuh pada Pratinjau atau Tautan Publik.</p>
                </div>
                <div>
                    <label class="form-label">Judul video</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="title" value="${I(t.title||"")}">
                </div>
            `:t.type==="link"?i=`
                <div><label class="form-label">Label tautan</label><input class="pkg-field w-full" maxlength="160" data-inspector-prop="text" value="${I(t.text||"")}"></div>
                <div><label class="form-label">Alamat tautan</label><input type="url" class="pkg-field w-full" maxlength="1000" data-inspector-prop="url" value="${I(t.url||"")}" placeholder="https://..."></div>
                <div>
                    <label class="form-label">Tampilan tautan</label>
                    <select class="pkg-field w-full" data-inspector-prop="linkStyle">
                        ${s("button","Tombol",t.linkStyle)}
                        ${s("card","Kartu",t.linkStyle)}
                        ${s("text","Teks",t.linkStyle)}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${$("Warna teks","color",t.color||"#ffffff")}
                    ${$("Latar","backgroundColor",q(t.backgroundColor,"#047857"))}
                </div>
            `:t.type==="shape"?i=`
                <div><label class="form-label">Teks di bentuk</label><textarea class="pkg-field w-full" rows="3" maxlength="1000" data-inspector-prop="text">${F(t.text||"")}</textarea></div>
                <div>
                    <label class="form-label">Bentuk</label>
                    <select class="pkg-field w-full" data-inspector-prop="shapeType">
                        ${s("circle","Lingkaran / oval",t.shapeType)}
                        ${s("rounded","Sudut membulat",t.shapeType)}
                        ${s("rectangle","Kotak",t.shapeType)}
                        ${s("hexagon","Segi enam",t.shapeType)}
                        ${s("custom","Radius buatan sendiri",t.shapeType)}
                    </select>
                </div>
                ${t.shapeType==="custom"?w("Radius sudut","borderRadius",t.borderRadius||24,0,240):""}
                ${w("Ukuran huruf","fontSize",t.fontSize||28,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${$("Warna teks","color",t.color||"#ffffff")}
                    ${$("Warna bentuk","backgroundColor",q(t.backgroundColor,"#0f766e"))}
                </div>
            `:t.type==="line"?i=`
                <div class="grid grid-cols-2 gap-3">
                    ${$("Warna garis","color",t.color||"#0f172a")}
                    ${w("Ketebalan","strokeWidth",t.strokeWidth||4,1,20)}
                </div>
                <div>
                    <label class="form-label">Pola garis</label>
                    <select class="pkg-field w-full" data-inspector-prop="lineStyle">
                        ${s("solid","Penuh",t.lineStyle)}
                        ${s("dashed","Putus-putus",t.lineStyle)}
                        ${s("dotted","Titik-titik",t.lineStyle)}
                    </select>
                </div>
                <div>
                    <label class="form-label">Ujung panah</label>
                    <select class="pkg-field w-full" data-inspector-prop="arrow">
                        ${s("none","Tanpa panah",t.arrow)}
                        ${s("end","Panah di akhir",t.arrow)}
                        ${s("start","Panah di awal",t.arrow)}
                        ${s("both","Panah dua arah",t.arrow)}
                    </select>
                </div>
                ${w("Rotasi garis","rotation",t.rotation||0,-180,180)}
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Atur panjang melalui penanda kiri/kanan. Gunakan rotasi untuk membuat garis miring atau tegak.</p>
            `:i=`
                <div>
                    <label class="form-label">Isi diagram (satu baris per kotak)</label>
                    <textarea class="pkg-field w-full" rows="6" data-inspector-prop="items">${F((t.items||[]).join(`
`))}</textarea>
                </div>
                <div>
                    <label class="form-label">Bentuk alur</label>
                    <select class="pkg-field w-full" data-inspector-prop="diagramType">
                        ${s("process","Proses mendatar",t.diagramType)}
                        ${s("cycle","Siklus",t.diagramType)}
                        ${s("hierarchy","Hierarki",t.diagramType)}
                        ${s("radial","Radial dengan pusat",t.diagramType)}
                    </select>
                </div>
                ${t.diagramType==="radial"?`
                    <div><label class="form-label">Teks pusat / logo</label><input class="pkg-field w-full" maxlength="120" data-inspector-prop="centerText" value="${I(t.centerText||"")}"></div>
                    <div>
                        <label class="form-label">Bentuk node</label>
                        <select class="pkg-field w-full" data-inspector-prop="nodeShape">
                            ${s("circle","Lingkaran",t.nodeShape||"circle")}
                            ${s("rounded","Sudut membulat",t.nodeShape)}
                            ${s("square","Kotak",t.nodeShape)}
                            ${s("hexagon","Segi enam",t.nodeShape)}
                        </select>
                    </div>
                `:""}
                <div class="grid grid-cols-2 gap-3">
                    ${$("Warna diagram","color",t.color||"#0f172a")}
                    ${$("Latar","backgroundColor",q(t.backgroundColor,"#ffffff"))}
                </div>
            `,r.inspector.innerHTML=`
            <div class="space-y-4" data-inspector-scope="element">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${oe(t.type)}
                </div>
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Seret bagian tengah elemen untuk memindahkan. Gunakan penanda hijau pada sisi dan sudut untuk mengubah ukuran langsung.</p>
                ${i}
                ${B(t)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `},D=a=>{const t=JSON.parse(a);e.presentation.canvas=t.canvas,e.presentation.title=t.title,e.presentation.description=t.description,e.presentation.backgroundColor=t.backgroundColor,e.presentation.pathMode=t.pathMode,r.title.value=t.title||"",r.description.value=t.description||"",r.background.value=t.backgroundColor||"#0f172a",r.pathMode.value=t.pathMode||"overview_between",W(e.presentation,e.selectedFrameId)||(e.selectedFrameId=e.presentation.canvas.frames[0]?.id||null),X(h(),e.selectedElementId)||(e.selectedElementId=null),S(),e.dirty=!0,e.changeVersion+=1,e.history.lastGroup=null,e.history.lastRecordedAt=0,e.manualCamera=!1,r.saveStatus.textContent="Perubahan dipulihkan · akan disimpan otomatis",r.saveStatus.classList.add("text-amber-600","dark:text-amber-300"),z(),M(),p(!1)},j=()=>{e.history.undo.length&&(e.history.redo.push(e.history.current),e.history.current=e.history.undo.pop(),D(e.history.current))},Y=()=>{e.history.redo.length&&(e.history.undo.push(e.history.current),e.history.current=e.history.redo.pop(),D(e.history.current))},G=a=>{e.selectedFrameId=a,e.selectedElementId=null,e.mode="focus",e.manualCamera=!1,p()};l.querySelector("[data-editor-overview]").addEventListener("click",()=>{e.mode="overview",e.selectedElementId=null,e.manualCamera=!1,p()}),l.querySelector("[data-editor-fit]").addEventListener("click",()=>{e.manualCamera=!1,P()}),l.querySelector("[data-add-frame]").addEventListener("click",()=>{const a=e.presentation.canvas.frames.length,t=180+a%2*1100,i=180+Math.floor(a/2)*560,n={id:E("frame"),title:`Frame ${a+1}`,x:t,y:i,width:800,height:450,backgroundColor:"#ffffff",shape:"rounded",borderRadius:22,elements:[]};e.presentation.canvas.frames.push(n),S(),e.selectedFrameId=n.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,v(),p()}),l.querySelector("[data-arrange-frames]").addEventListener("click",()=>{const a=e.presentation.canvas.frames,t=Math.max(...a.map(d=>Number(d.width||800))),i=Math.max(...a.map(d=>Number(d.height||450))),n=160,o=140;a.forEach((d,m)=>{d.x=120+m%2*(t+n),d.y=120+Math.floor(m/2)*(i+o)}),e.mode="overview",e.selectedElementId=null,e.manualCamera=!1,S(),v(),p()}),l.querySelector("[data-add-text]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:E("element"),type:"text",x:70,y:80,width:Math.max(240,a.width-140),height:130,rotation:0,text:"Tulis materi di sini",fontSize:36,color:"#0f172a",backgroundColor:"transparent",align:"left",bold:!1};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,v(),p()}),l.querySelector("[data-add-diagram]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:E("element"),type:"diagram",x:70,y:130,width:Math.max(360,a.width-140),height:180,rotation:0,color:"#047857",backgroundColor:"transparent",diagramType:"process",items:["Pembuka","Pembahasan","Kesimpulan"]};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,v(),p()}),l.querySelector("[data-add-youtube]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:E("element"),type:"youtube",x:90,y:90,width:Math.min(560,a.width-180),height:Math.min(315,a.height-150),rotation:0,youtubeUrl:"",youtubeId:"",title:"Video YouTube",color:"#ffffff",backgroundColor:"#0f172a"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,v(),p()}),l.querySelector("[data-add-link]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:E("element"),type:"link",x:90,y:300,width:260,height:70,rotation:0,text:"Buka tautan",url:"https://",linkStyle:"button",color:"#ffffff",backgroundColor:"#047857"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,v(),p()}),l.querySelector("[data-add-shape]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:E("element"),type:"shape",x:110,y:140,width:220,height:150,rotation:0,text:"Isi bentuk",shapeType:"rounded",borderRadius:24,fontSize:28,color:"#ffffff",backgroundColor:"#0f766e"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,v(),p()}),l.querySelector("[data-add-line]").addEventListener("click",()=>{const a=h();if(!a)return;const t={id:E("element"),type:"line",x:100,y:Math.max(70,Math.round(a.height/2)-20),width:Math.max(220,a.width-200),height:40,rotation:0,color:"#0f766e",backgroundColor:"transparent",strokeWidth:4,lineStyle:"solid",arrow:"none"};a.elements.push(t),e.selectedElementId=t.id,e.mode="focus",e.manualCamera=!1,v(),p()}),l.querySelector("[data-add-image]").addEventListener("click",()=>{h()&&r.imageInput.click()}),l.querySelector("[data-add-logo]").addEventListener("click",()=>{h()&&r.logoInput.click()});const U=async(a,t)=>{const i=a.files?.[0],n=h();if(!i||!n)return;const o=new FormData;o.append("image",i),r.saveStatus.textContent="Mengunggah gambar...";try{const d=await fetch(l.dataset.uploadUrl,{method:"POST",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:o}),m=await d.json();if(!d.ok)throw new Error(m.message||"Gambar gagal diunggah.");e.presentation.assets[String(m.asset.id)]=m.asset;const u={id:E("element"),type:t,assetId:m.asset.id,x:90,y:80,width:Math.min(t==="logo"?220:420,n.width-180),height:Math.min(t==="logo"?220:280,n.height-140),rotation:0,alt:m.asset.name,fit:t==="logo"?"contain":"cover",shape:t==="logo"?"circle":"rounded",color:"#0f172a",backgroundColor:"transparent"};n.elements.push(u),e.selectedElementId=u.id,e.mode="focus",e.manualCamera=!1,v(),p()}catch(d){r.saveStatus.textContent=d.message,r.saveStatus.classList.add("text-red-600")}finally{a.value=""}};r.imageInput.addEventListener("change",()=>U(r.imageInput,"image")),r.logoInput.addEventListener("change",()=>U(r.logoInput,"logo")),r.frameList.addEventListener("click",a=>{const t=a.target.closest("[data-frame-focus]");if(t){G(t.dataset.frameFocus);return}const i=a.target.closest("[data-frame-move]");if(!i)return;const n=e.presentation.canvas.frames,o=n.findIndex(m=>m.id===i.dataset.frameId),d=i.dataset.frameMove==="up"?o-1:o+1;o<0||d<0||d>=n.length||([n[o],n[d]]=[n[d],n[o]],v(),N())}),r.inspector.addEventListener("input",a=>{const t=a.target.closest("[data-inspector-prop]");if(!t)return;const i=t.closest("[data-inspector-scope]")?.dataset.inspectorScope,n=i==="frame"?h():K();if(!n||i==="frame"&&["width","height"].includes(t.dataset.inspectorProp))return;let o=t.type==="checkbox"?t.checked:t.value;["x","y","width","height","fontSize","borderRadius","strokeWidth","rotation"].includes(t.dataset.inspectorProp)&&(o=Number(o)),t.dataset.inspectorProp==="items"&&(o=String(o).split(`
`).map(d=>d.trim()).filter(Boolean).slice(0,8)),n[t.dataset.inspectorProp]=o,t.dataset.inspectorProp==="youtubeUrl"&&(n.youtubeId=se(o)),i==="frame"&&S(),v(`inspector:${i}:${n.id}:${t.dataset.inspectorProp}`),_({stage:r.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,overview:e.mode==="overview"}),e.manualCamera?L():P(!1),i==="frame"&&t.dataset.inspectorProp==="title"&&N(),["shape","shapeType","diagramType"].includes(t.dataset.inspectorProp)&&H()}),r.inspector.addEventListener("change",a=>{const t=a.target.closest("[data-inspector-prop]"),i=t?.closest("[data-inspector-scope]")?.dataset.inspectorScope;if(!t||i!=="frame"||!["width","height"].includes(t.dataset.inspectorProp))return;const n=h();n&&(A(n,t.dataset.inspectorProp==="width"?Number(t.value):n.width,t.dataset.inspectorProp==="height"?Number(t.value):n.height),v(),p(!1))}),r.inspector.addEventListener("click",a=>{const t=a.target.closest("[data-frame-size]");if(t){const i=h(),[n,o]=t.dataset.frameSize.split("x").map(Number);A(i,n,o),v(),p(!1);return}if(a.target.closest("[data-focus-selected-frame]")){e.mode="focus",e.manualCamera=!1,p();return}if(a.target.closest("[data-delete-selected-element]")){const i=h();i.elements=i.elements.filter(n=>n.id!==e.selectedElementId),e.selectedElementId=null,v(),p();return}if(a.target.closest("[data-delete-selected-frame]")&&e.presentation.canvas.frames.length>1){const i=e.presentation.canvas.frames,n=i.findIndex(o=>o.id===e.selectedFrameId);i.splice(n,1),e.selectedFrameId=i[Math.max(0,n-1)]?.id||i[0]?.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,S(),v(),p()}});const O=a=>{e.presentation.title=r.title.value,e.presentation.description=r.description.value,e.presentation.backgroundColor=r.background.value,e.presentation.pathMode=r.pathMode.value,v(`metadata:${a.target.dataset.editorTitle!==void 0?"title":a.target.dataset.editorDescription!==void 0?"description":a.target.dataset.editorBackground!==void 0?"background":"path"}`),document.activeElement===r.background&&p(!1)};[r.title,r.description,r.background,r.pathMode].forEach(a=>{a.addEventListener("input",O),a.addEventListener("change",O)}),r.viewport.addEventListener("wheel",a=>{if(a.preventDefault(),a.ctrlKey||a.metaKey?e.cameraScale=ee(r.viewport,r.stage,te(a.deltaY),a.clientX,a.clientY,{minimumScale:.03,maximumScale:4}):e.cameraScale=ae(r.stage,-a.deltaX,-a.deltaY),e.manualCamera=!0,(a.ctrlKey||a.metaKey)&&e.mode==="focus"&&e.cameraScale<=e.focusScale*.72){e.mode="overview",e.selectedElementId=null,p(!1);return}L()},{passive:!1});const Z=()=>{const a=e.drag;a&&(a.kind==="frame-resize"?(a.target.width=a.originWidth,a.target.height=a.originHeight,a.frameElements.forEach(t=>{t.item.x=t.x,t.item.y=t.y,t.item.width=t.width,t.item.height=t.height,t.item.type==="text"&&(t.item.fontSize=t.fontSize)})):a.kind==="element-resize"&&a.target?(a.target.x=a.originX,a.target.y=a.originY,a.target.width=a.originWidth,a.target.height=a.originHeight):a.target&&(a.target.x=a.originX,a.target.y=a.originY),e.drag=null,e.manualCamera=!0,p(!1))};T=re(r.viewport,r.stage,{minimumScale:.03,maximumScale:4,onStart:()=>{e.overviewCandidate=!1,Z()},onUpdate:a=>{e.cameraScale=a,e.manualCamera=!0,e.mode==="focus"&&a<=e.focusScale*.72&&(e.overviewCandidate=!0),L()},onEnd:()=>{!e.overviewCandidate||e.mode!=="focus"||(e.mode="overview",e.selectedElementId=null,e.manualCamera=!0,e.overviewCandidate=!1,p(!1))},onTap:a=>{if(e.mode!=="overview"||e.drag?.moved)return!1;const t=a.target.closest?.("[data-frame-id]");return t?(G(t.dataset.frameId),!0):!1}}),r.viewport.addEventListener("pointerdown",a=>{if(a.button!==0||T?.isActive())return;e.cameraScale=ie(r.stage);const t=a.target.closest("[data-frame-id]"),i=a.target.closest("[data-frame-resize]"),n=a.target.closest("[data-element-resize]");if(!t)return;const o=W(e.presentation,t.dataset.frameId),d=a.target.closest(".pkg-presentation-element[data-element-id]"),m=n?.dataset.elementId||d?.dataset.elementId,u=X(o,m),y=u?t.querySelector(`.pkg-presentation-element[data-element-id="${CSS.escape(u.id)}"]`):null,k=n?.closest(".pkg-presentation-element-controls")||(u?t.querySelector(`.pkg-presentation-element-controls[data-element-id="${CSS.escape(u.id)}"]`):null);e.selectedFrameId=o.id,e.selectedElementId=e.mode==="focus"&&u?u.id:null;const g=(o.elements||[]).map(c=>({item:c,x:Number(c.x||0),y:Number(c.y||0),width:Number(c.width||100),height:Number(c.height||80),fontSize:Number(c.fontSize||32)}));e.drag={kind:e.mode==="overview"?i?"frame-resize":"frame":n?"element-resize":u?"element":null,target:e.mode==="overview"?o:u,node:e.mode==="overview"?t:y,controlsNode:k,frame:o,resizeDirection:n?.dataset.elementResize||null,startX:a.clientX,startY:a.clientY,originX:e.mode==="overview"?o.x:u?.x,originY:e.mode==="overview"?o.y:u?.y,originWidth:e.mode==="overview"?o.width:u?.width,originHeight:e.mode==="overview"?o.height:u?.height,frameElements:g,moved:!1},r.viewport.setPointerCapture(a.pointerId),H()}),r.viewport.addEventListener("pointermove",a=>{if(T?.isActive()||!e.drag?.kind||!e.drag.target)return;const t=a.clientX-e.drag.startX,i=a.clientY-e.drag.startY,n=e.drag.kind.includes("resize")?3:7;if(!e.drag.moved&&Math.hypot(t,i)<n)return;e.drag.moved=!0;const o=t/e.cameraScale,d=i/e.cameraScale;if(e.drag.kind==="frame-resize"){const m=b(e.drag.originWidth+o,320,1600,e.drag.originWidth),u=b(e.drag.originHeight+d,180,900,e.drag.originHeight),y=m/e.drag.originWidth,k=u/e.drag.originHeight,g=Math.min(y,k);e.drag.target.width=m,e.drag.target.height=u,e.drag.node.style.width=`${m}px`,e.drag.node.style.height=`${u}px`,e.drag.frameElements.forEach(c=>{c.item.x=c.x*y,c.item.y=c.y*k,c.item.width=Math.max(40,c.width*y),c.item.height=Math.max(30,c.height*k),c.item.type==="text"&&(c.item.fontSize=b(c.fontSize*g,10,160,c.fontSize));const x=e.drag.node.querySelector(`[data-element-id="${CSS.escape(c.item.id)}"]`);x&&(x.style.left=`${c.item.x}px`,x.style.top=`${c.item.y}px`,x.style.width=`${c.item.width}px`,x.style.height=`${c.item.height}px`,c.item.type==="text"&&(x.style.fontSize=`${c.item.fontSize}px`))})}else if(e.drag.kind==="element-resize"){const m=e.drag.resizeDirection||"se",u=40,y=30;let k=e.drag.originX,g=e.drag.originY,c=e.drag.originWidth,x=e.drag.originHeight;m.includes("e")&&(c=b(e.drag.originWidth+o,u,Math.max(u,e.drag.frame.width-e.drag.originX),e.drag.originWidth)),m.includes("s")&&(x=b(e.drag.originHeight+d,y,Math.max(y,e.drag.frame.height-e.drag.originY),e.drag.originHeight)),m.includes("w")&&(k=b(e.drag.originX+o,0,e.drag.originX+e.drag.originWidth-u,e.drag.originX),c=e.drag.originWidth+(e.drag.originX-k)),m.includes("n")&&(g=b(e.drag.originY+d,0,e.drag.originY+e.drag.originHeight-y,e.drag.originY),x=e.drag.originHeight+(e.drag.originY-g)),Object.assign(e.drag.target,{x:k,y:g,width:c,height:x}),e.drag.node.style.left=`${k}px`,e.drag.node.style.top=`${g}px`,e.drag.node.style.width=`${c}px`,e.drag.node.style.height=`${x}px`,e.drag.controlsNode&&(e.drag.controlsNode.style.left=`${k}px`,e.drag.controlsNode.style.top=`${g}px`,e.drag.controlsNode.style.width=`${c}px`,e.drag.controlsNode.style.height=`${x}px`)}else{const m=Math.max(0,e.drag.frame.width-e.drag.target.width),u=Math.max(0,e.drag.frame.height-e.drag.target.height);e.drag.target.x=b(e.drag.originX+o,0,m,e.drag.originX),e.drag.target.y=b(e.drag.originY+d,0,u,e.drag.originY),e.drag.node.style.left=`${e.drag.target.x}px`,e.drag.node.style.top=`${e.drag.target.y}px`,e.drag.controlsNode&&(e.drag.controlsNode.style.left=`${e.drag.target.x}px`,e.drag.controlsNode.style.top=`${e.drag.target.y}px`)}});const V=a=>{if(T?.shouldSuppressTap()){e.drag=null;return}if(!e.drag)return;const t=e.drag;if(e.drag=null,r.viewport.hasPointerCapture(a.pointerId)&&r.viewport.releasePointerCapture(a.pointerId),t.moved)S(),v(),p(!1);else if(e.mode==="overview"&&t.kind==="frame"){e.mode="focus",e.manualCamera=!1,p();return}else if(e.mode==="focus"&&t.kind==="element"){p(!1);return}N()};r.viewport.addEventListener("pointerup",V),r.viewport.addEventListener("pointercancel",V);async function C(){if(window.clearTimeout(e.saveTimer),e.activeSave){const i=await e.activeSave;return i&&e.dirty?C():i}if(!e.dirty)return!0;const a=e.changeVersion;e.saving=!0,r.saveStatus.textContent="Menyimpan...",e.activeSave=(async()=>{try{S();const i=await fetch(l.dataset.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:JSON.stringify({title:r.title.value,description:r.description.value,background_color:r.background.value,path_mode:r.pathMode.value,canvas_data:e.presentation.canvas})}),n=await i.json();if(!i.ok)throw new Error(n.message||Object.values(n.errors||{})[0]?.[0]||"Presentasi gagal disimpan.");return e.changeVersion===a&&(e.dirty=!1,r.saveStatus.textContent="Semua perubahan tersimpan",r.saveStatus.classList.remove("text-amber-600","dark:text-amber-300","text-red-600")),!0}catch(i){return r.saveStatus.textContent=i.message,r.saveStatus.classList.add("text-red-600"),!1}finally{e.saving=!1}})();const t=await e.activeSave;return e.activeSave=null,t&&e.dirty?C():t}r.undo.addEventListener("click",j),r.redo.addEventListener("click",Y),document.addEventListener("keydown",a=>{if(!(a.ctrlKey||a.metaKey)||a.altKey)return;const t=a.key.toLowerCase();t==="z"?(a.preventDefault(),a.shiftKey?Y():j()):t==="y"&&(a.preventDefault(),Y())}),l.querySelector("[data-editor-save]").addEventListener("click",C),l.querySelectorAll("[data-export-link]").forEach(a=>{a.addEventListener("click",async t=>{if(!e.dirty)return;t.preventDefault(),await C()&&window.location.assign(a.href)})}),l.querySelectorAll("[data-save-before-open]").forEach(a=>{a.addEventListener("click",async t=>{if(!e.dirty)return;t.preventDefault();const i=a.target==="_blank"?window.open("about:blank","_blank"):null;await C()?i?i.location.href=a.href:window.location.assign(a.href):i&&i.close()})}),l.querySelectorAll("[data-publish-form]").forEach(a=>{a.addEventListener("submit",async t=>{e.dirty&&(t.preventDefault(),await C()&&a.submit())})}),window.addEventListener("beforeunload",a=>{e.dirty&&(a.preventDefault(),a.returnValue="")}),new ResizeObserver(()=>{e.manualCamera=!1,P(!1)}).observe(r.viewport),p(!1)}function w(f,e,r,T,M){return`<div><label class="form-label">${f}</label><input type="number" class="pkg-field w-full" min="${T}" max="${M}" data-inspector-prop="${e}" value="${Math.round(r)}"></div>`}function $(f,e,r){return`<div><label class="form-label">${f}</label><input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="${e}" value="${r}"></div>`}function s(f,e,r){return`<option value="${f}" ${f===r?"selected":""}>${e}</option>`}function q(f,e){return/^#[0-9a-f]{6}$/i.test(f||"")?f:e}function oe(f){return{text:"Elemen Teks",image:"Elemen Gambar",logo:"Elemen Logo",youtube:"Elemen YouTube",link:"Elemen Tautan",shape:"Elemen Bentuk",line:"Elemen Garis",diagram:"Elemen Diagram"}[f]||"Elemen"}function se(f){return String(f||"").trim().match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/i)?.[1]||""}function F(f){return String(f??"").replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"})[e])}function I(f){return F(f).replace(/\n/g,"&#10;")}
