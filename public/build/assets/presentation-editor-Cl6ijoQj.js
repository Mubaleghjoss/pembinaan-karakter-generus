import{d as se,p as E,r as Q,z as le,a as oe,b as de,c as ce,s as ue,f as R,e as A,g as x,h as me}from"./presentation-canvas-8JJhpxrT.js";const c=document.getElementById("presentation-editor");if(c){const m=se(c.dataset.presentationPayload);m.canvas.elements=Array.isArray(m.canvas.elements)?m.canvas.elements:[];const e={presentation:m,selectedFrameId:m.canvas.frames[0]?.id||null,selectedElementId:null,selectedCanvasElementId:null,mode:"overview",dirty:!1,saving:!1,changeVersion:0,saveTimer:null,activeSave:null,cameraScale:1,focusScale:1,overviewCandidate:!1,manualCamera:!1,drag:null,history:{current:null,undo:[],redo:[],lastGroup:null,lastRecordedAt:0}},n={viewport:c.querySelector("[data-editor-viewport]"),stage:c.querySelector("[data-editor-stage]"),frameList:c.querySelector("[data-frame-list]"),inspector:c.querySelector("[data-editor-inspector]"),hint:c.querySelector("[data-editor-hint]"),saveStatus:c.querySelector("[data-save-status]"),title:c.querySelector("[data-editor-title]"),description:c.querySelector("[data-editor-description]"),background:c.querySelector("[data-editor-background]"),pathMode:c.querySelector("[data-editor-path-mode]"),imageInput:c.querySelector("[data-image-input]"),logoInput:c.querySelector("[data-logo-input]"),undo:c.querySelector("[data-editor-undo]"),redo:c.querySelector("[data-editor-redo]")};let L=null;n.title.value=m.title||"",n.description.value=m.description||"",n.background.value=m.backgroundColor||"#0f172a",n.pathMode.value=m.pathMode||"overview_between";const M=()=>{window.clearTimeout(e.saveTimer),e.saveTimer=window.setTimeout(()=>I(),1200)},D=()=>JSON.stringify({canvas:e.presentation.canvas,title:e.presentation.title,description:e.presentation.description,backgroundColor:e.presentation.backgroundColor,pathMode:e.presentation.pathMode}),z=()=>{n.undo.disabled=e.history.undo.length===0,n.redo.disabled=e.history.redo.length===0},ee=(t=null)=>{const a=D();if(e.history.current===null){e.history.current=a,z();return}if(a===e.history.current)return;const r=performance.now();t&&e.history.lastGroup===t&&r-e.history.lastRecordedAt<700||(e.history.undo.push(e.history.current),e.history.undo.length>80&&e.history.undo.shift()),e.history.current=a,e.history.redo=[],e.history.lastGroup=t,e.history.lastRecordedAt=r,z()},v=(t=null)=>{ee(t),e.dirty=!0,e.changeVersion+=1,n.saveStatus.textContent="Belum disimpan · akan disimpan otomatis",n.saveStatus.classList.add("text-amber-600","dark:text-amber-300"),M()},b=()=>R(e.presentation,e.selectedFrameId),K=()=>A(b(),e.selectedElementId),W=()=>e.presentation.canvas.elements.find(t=>t.id===e.selectedCanvasElementId)||null,w=()=>{const t=e.presentation.canvas.frames,a=t.reduce((l,o)=>Math.max(l,Number(o.x||0)+Number(o.width||0)+120),1200),r=t.reduce((l,o)=>Math.max(l,Number(o.y||0)+Number(o.height||0)+120),800),i=e.presentation.canvas.elements.reduce((l,o)=>Math.max(l,Number(o.x||0)+Number(o.width||0)+120),a),s=e.presentation.canvas.elements.reduce((l,o)=>Math.max(l,Number(o.y||0)+Number(o.height||0)+120),r);e.presentation.canvas.width=x(i,1200,7e3,2400),e.presentation.canvas.height=x(s,800,12500,1400)},B=(t,a,r)=>{const i=Math.max(1,Number(t.width||800)),s=Math.max(1,Number(t.height||450)),l=x(a,320,1600,i),o=x(r,180,900,s),y=l/i,k=o/s,g=Math.min(y,k);(t.elements||[]).forEach(h=>{h.x=Number(h.x||0)*y,h.y=Number(h.y||0)*k,h.width=Math.max(40,Number(h.width||100)*y),h.height=Math.max(30,Number(h.height||80)*k),h.type==="text"&&(h.fontSize=x(Number(h.fontSize||32)*g,10,160,32))}),t.width=l,t.height=o,w()};w(),e.history.current=D(),z();const N=()=>{const t=e.mode==="focus"?b():null,a=t?`Fokus: ${t.title}`:"Mode Overview";n.hint.textContent=`${a} · ${Math.round(e.cameraScale*100)}%`},P=(t=!0)=>{const a=e.mode==="focus"?b():null;e.cameraScale=me(n.viewport,n.stage,e.presentation.canvas,a,t),a&&(e.focusScale=e.cameraScale),e.manualCamera=!1,N()},u=(t=!0)=>{Q({stage:n.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,selectedCanvasElementId:e.selectedCanvasElementId,overview:e.mode==="overview",editable:!0}),q(),F(),requestAnimationFrame(()=>{if(e.manualCamera){N();return}P(t)})},q=()=>{n.frameList.replaceChildren(),e.presentation.canvas.frames.forEach((t,a)=>{const r=document.createElement("div");r.className=`pkg-presentation-frame-list-item${t.id===e.selectedFrameId?" is-selected":""}`;const i=document.createElement("button");i.type="button",i.className="min-w-0 flex-1 text-left",i.dataset.frameFocus=t.id,i.innerHTML=`<span class="block text-xs font-bold text-emerald-600">${a+1}</span>`;const s=document.createElement("span");s.className="block truncate text-sm font-semibold text-gray-900 dark:text-white",s.textContent=t.title,i.appendChild(s),r.appendChild(i);const l=document.createElement("div");l.className="flex gap-1",l.innerHTML=`
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="up" data-frame-id="${t.id}" aria-label="Naik">↑</button>
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="down" data-frame-id="${t.id}" aria-label="Turun">↓</button>
            `,r.appendChild(l),n.frameList.appendChild(r)})},j=(t,a=!1,r=!1)=>`
        <div class="grid grid-cols-2 gap-3">
            ${$("X","x",t.x,0,r?6800:5e3)}
            ${$("Y","y",t.y,0,r||a?12300:1100)}
            ${$("Lebar","width",t.width,a?320:40,1600)}
            ${$("Tinggi","height",t.height,a?180:30,900)}
        </div>
    `,F=()=>{const t=b(),a=W(),r=a||K(),i=!!a;if(!t&&!a){n.inspector.innerHTML='<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';return}if(!r&&t){n.inspector.innerHTML=`
                <div class="space-y-4" data-inspector-scope="frame">
                    <div>
                        <label class="form-label">Judul frame</label>
                        <input class="pkg-field w-full" maxlength="120" data-inspector-prop="title" value="${T(t.title)}">
                    </div>
                    <div>
                        <label class="form-label">Warna frame</label>
                        <input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="backgroundColor" value="${t.backgroundColor||"#ffffff"}">
                    </div>
                    <div>
                        <label class="form-label">Bentuk frame</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${d("rounded","Sudut membulat",t.shape||"rounded")}
                            ${d("rectangle","Kotak",t.shape)}
                            ${d("circle","Lingkaran / oval",t.shape)}
                            ${d("hexagon","Segi enam",t.shape)}
                            ${d("custom","Radius buatan sendiri",t.shape)}
                        </select>
                    </div>
                    ${t.shape==="custom"?$("Radius sudut","borderRadius",t.borderRadius||22,0,240):""}
                    <div>
                        <label class="form-label">Ukuran frame</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="pkg-frame-size-button" data-frame-size="560x315">Kecil</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="800x450">Sedang</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="1120x630">Besar</button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Gunakan preset, isi ukuran manual, atau seret pegangan hijau di sudut kanan bawah frame.</p>
                    </div>
                    ${j(t,!0)}
                    <button type="button" class="btn-primary w-full justify-center" data-focus-selected-frame>Fokuskan Frame</button>
                    <button type="button" class="btn-danger w-full justify-center" data-delete-selected-frame ${e.presentation.canvas.frames.length<=1?"disabled":""}>Hapus Frame</button>
                </div>
            `;return}let s="";r.type==="text"?s=`
                <div>
                    <label class="form-label">Isi teks</label>
                    <textarea class="pkg-field w-full" rows="5" maxlength="5000" data-inspector-prop="text">${Y(r.text||"")}</textarea>
                </div>
                ${$("Ukuran huruf","fontSize",r.fontSize||32,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${C("Warna teks","color",r.color||"#0f172a")}
                    ${C("Latar","backgroundColor",H(r.backgroundColor,"#ffffff"))}
                </div>
                <div>
                    <label class="form-label">Perataan</label>
                    <select class="pkg-field w-full" data-inspector-prop="align">
                        ${d("left","Kiri",r.align)}
                        ${d("center","Tengah",r.align)}
                        ${d("right","Kanan",r.align)}
                    </select>
                </div>
                <label class="pkg-check"><input type="checkbox" data-inspector-prop="bold" ${r.bold?"checked":""}><span>Teks tebal</span></label>
            `:r.type==="image"||r.type==="logo"?s=`
                <div>
                    <label class="form-label">Teks alternatif</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="alt" value="${T(r.alt||"")}">
                </div>
                <div>
                    <label class="form-label">Penyesuaian gambar</label>
                    <select class="pkg-field w-full" data-inspector-prop="fit">
                        ${d("cover","Penuhi area",r.fit)}
                        ${d("contain","Tampilkan utuh",r.fit)}
                    </select>
                </div>
                ${r.type==="logo"?`
                    <div>
                        <label class="form-label">Bentuk logo</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${d("circle","Lingkaran",r.shape||"circle")}
                            ${d("rounded","Sudut membulat",r.shape)}
                            ${d("square","Kotak",r.shape)}
                            ${d("hexagon","Segi enam",r.shape)}
                        </select>
                    </div>
                `:""}
            `:r.type==="youtube"?s=`
                <div>
                    <label class="form-label">Link YouTube</label>
                    <input type="url" class="pkg-field w-full" maxlength="500" data-inspector-prop="youtubeUrl" value="${T(r.youtubeUrl||"")}" placeholder="https://youtu.be/...">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Video dapat diputar dan dibuka layar penuh pada Pratinjau atau Tautan Publik.</p>
                </div>
                <div>
                    <label class="form-label">Judul video</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="title" value="${T(r.title||"")}">
                </div>
            `:r.type==="link"?s=`
                <div><label class="form-label">Label tautan</label><input class="pkg-field w-full" maxlength="160" data-inspector-prop="text" value="${T(r.text||"")}"></div>
                <div><label class="form-label">Alamat tautan</label><input type="url" class="pkg-field w-full" maxlength="1000" data-inspector-prop="url" value="${T(r.url||"")}" placeholder="https://..."></div>
                <div>
                    <label class="form-label">Tampilan tautan</label>
                    <select class="pkg-field w-full" data-inspector-prop="linkStyle">
                        ${d("button","Tombol",r.linkStyle)}
                        ${d("card","Kartu",r.linkStyle)}
                        ${d("text","Teks",r.linkStyle)}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${C("Warna teks","color",r.color||"#ffffff")}
                    ${C("Latar","backgroundColor",H(r.backgroundColor,"#047857"))}
                </div>
            `:r.type==="shape"?s=`
                <div><label class="form-label">Teks di bentuk</label><textarea class="pkg-field w-full" rows="3" maxlength="1000" data-inspector-prop="text">${Y(r.text||"")}</textarea></div>
                <div>
                    <label class="form-label">Bentuk</label>
                    <select class="pkg-field w-full" data-inspector-prop="shapeType">
                        ${d("circle","Lingkaran / oval",r.shapeType)}
                        ${d("rounded","Sudut membulat",r.shapeType)}
                        ${d("rectangle","Kotak",r.shapeType)}
                        ${d("hexagon","Segi enam",r.shapeType)}
                        ${d("custom","Radius buatan sendiri",r.shapeType)}
                    </select>
                </div>
                ${r.shapeType==="custom"?$("Radius sudut","borderRadius",r.borderRadius||24,0,240):""}
                ${$("Ukuran huruf","fontSize",r.fontSize||28,10,160)}
                <div class="grid grid-cols-2 gap-3">
                    ${C("Warna teks","color",r.color||"#ffffff")}
                    ${C("Warna bentuk","backgroundColor",H(r.backgroundColor,"#0f766e"))}
                </div>
            `:r.type==="line"?s=`
                <div class="grid grid-cols-2 gap-3">
                    ${C("Warna garis","color",r.color||"#0f172a")}
                    ${$("Ketebalan","strokeWidth",r.strokeWidth||4,1,20)}
                </div>
                <div>
                    <label class="form-label">Pola garis</label>
                    <select class="pkg-field w-full" data-inspector-prop="lineStyle">
                        ${d("solid","Penuh",r.lineStyle)}
                        ${d("dashed","Putus-putus",r.lineStyle)}
                        ${d("dotted","Titik-titik",r.lineStyle)}
                    </select>
                </div>
                <div>
                    <label class="form-label">Ujung panah</label>
                    <select class="pkg-field w-full" data-inspector-prop="arrow">
                        ${d("none","Tanpa panah",r.arrow)}
                        ${d("end","Panah di akhir",r.arrow)}
                        ${d("start","Panah di awal",r.arrow)}
                        ${d("both","Panah dua arah",r.arrow)}
                    </select>
                </div>
                ${$("Rotasi garis","rotation",r.rotation||0,-180,180)}
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Atur panjang melalui penanda kiri/kanan. Gunakan rotasi untuk membuat garis miring atau tegak.</p>
            `:s=`
                <div>
                    <label class="form-label">Isi diagram (satu baris per kotak)</label>
                    <textarea class="pkg-field w-full" rows="6" data-inspector-prop="items">${Y((r.items||[]).join(`
`))}</textarea>
                </div>
                <div>
                    <label class="form-label">Bentuk alur</label>
                    <select class="pkg-field w-full" data-inspector-prop="diagramType">
                        ${d("process","Proses mendatar",r.diagramType)}
                        ${d("cycle","Siklus",r.diagramType)}
                        ${d("hierarchy","Hierarki",r.diagramType)}
                        ${d("radial","Radial dengan pusat",r.diagramType)}
                    </select>
                </div>
                ${r.diagramType==="radial"?`
                    <div><label class="form-label">Teks pusat / logo</label><input class="pkg-field w-full" maxlength="120" data-inspector-prop="centerText" value="${T(r.centerText||"")}"></div>
                    <div>
                        <label class="form-label">Bentuk node</label>
                        <select class="pkg-field w-full" data-inspector-prop="nodeShape">
                            ${d("circle","Lingkaran",r.nodeShape||"circle")}
                            ${d("rounded","Sudut membulat",r.nodeShape)}
                            ${d("square","Kotak",r.nodeShape)}
                            ${d("hexagon","Segi enam",r.nodeShape)}
                        </select>
                    </div>
                `:""}
                <div class="grid grid-cols-2 gap-3">
                    ${C("Warna diagram","color",r.color||"#0f172a")}
                    ${C("Latar","backgroundColor",H(r.backgroundColor,"#ffffff"))}
                </div>
            `,n.inspector.innerHTML=`
            <div class="space-y-4" data-inspector-scope="${i?"canvas-element":"element"}">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${pe(r.type)}${i?" di Luar Frame":""}
                </div>
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Seret bagian tengah elemen untuk memindahkan. Gunakan penanda hijau pada sisi dan sudut untuk mengubah ukuran langsung.${i?" Elemen ini tampil pada Overview dan berada di luar isi frame.":""}</p>
                ${s}
                ${j(r,!1,i)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `},G=t=>{const a=JSON.parse(t);e.presentation.canvas=a.canvas,e.presentation.canvas.elements=Array.isArray(e.presentation.canvas.elements)?e.presentation.canvas.elements:[],e.presentation.title=a.title,e.presentation.description=a.description,e.presentation.backgroundColor=a.backgroundColor,e.presentation.pathMode=a.pathMode,n.title.value=a.title||"",n.description.value=a.description||"",n.background.value=a.backgroundColor||"#0f172a",n.pathMode.value=a.pathMode||"overview_between",R(e.presentation,e.selectedFrameId)||(e.selectedFrameId=e.presentation.canvas.frames[0]?.id||null),A(b(),e.selectedElementId)||(e.selectedElementId=null),W()||(e.selectedCanvasElementId=null),w(),e.dirty=!0,e.changeVersion+=1,e.history.lastGroup=null,e.history.lastRecordedAt=0,e.manualCamera=!1,n.saveStatus.textContent="Perubahan dipulihkan · akan disimpan otomatis",n.saveStatus.classList.add("text-amber-600","dark:text-amber-300"),z(),M(),u(!1)},O=()=>{e.history.undo.length&&(e.history.redo.push(e.history.current),e.history.current=e.history.undo.pop(),G(e.history.current))},X=()=>{e.history.redo.length&&(e.history.undo.push(e.history.current),e.history.current=e.history.redo.pop(),G(e.history.current))},U=t=>{e.selectedFrameId=t,e.selectedElementId=null,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,u()};c.querySelector("[data-editor-overview]").addEventListener("click",()=>{e.mode="overview",e.selectedElementId=null,e.selectedCanvasElementId=null,e.manualCamera=!1,u()}),c.querySelector("[data-editor-fit]").addEventListener("click",()=>{e.manualCamera=!1,P()}),c.querySelector("[data-add-frame]").addEventListener("click",()=>{const t=e.presentation.canvas.frames.length,a=180+t%2*1100,r=180+Math.floor(t/2)*560,i={id:E("frame"),title:`Frame ${t+1}`,x:a,y:r,width:800,height:450,backgroundColor:"#ffffff",shape:"rounded",borderRadius:22,elements:[]};e.presentation.canvas.frames.push(i),w(),e.selectedFrameId=i.id,e.selectedElementId=null,e.selectedCanvasElementId=null,e.mode="overview",e.manualCamera=!1,v(),u()}),c.querySelector("[data-arrange-frames]").addEventListener("click",()=>{const t=e.presentation.canvas.frames,a=Math.max(...t.map(l=>Number(l.width||800))),r=Math.max(...t.map(l=>Number(l.height||450))),i=160,s=140;t.forEach((l,o)=>{l.x=120+o%2*(a+i),l.y=120+Math.floor(o/2)*(r+s)}),e.mode="overview",e.selectedElementId=null,e.selectedCanvasElementId=null,e.manualCamera=!1,w(),v(),u()}),c.querySelector("[data-add-text]").addEventListener("click",()=>{const t=b();if(!t)return;const a={id:E("element"),type:"text",x:70,y:80,width:Math.max(240,t.width-140),height:130,rotation:0,text:"Tulis materi di sini",fontSize:36,color:"#0f172a",backgroundColor:"transparent",align:"left",bold:!1};t.elements.push(a),e.selectedElementId=a.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()}),c.querySelector("[data-add-diagram]").addEventListener("click",()=>{const t=b();if(!t)return;const a={id:E("element"),type:"diagram",x:70,y:130,width:Math.max(360,t.width-140),height:180,rotation:0,color:"#047857",backgroundColor:"transparent",diagramType:"process",items:["Pembuka","Pembahasan","Kesimpulan"]};t.elements.push(a),e.selectedElementId=a.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()}),c.querySelector("[data-add-youtube]").addEventListener("click",()=>{const t=b();if(!t)return;const a={id:E("element"),type:"youtube",x:90,y:90,width:Math.min(560,t.width-180),height:Math.min(315,t.height-150),rotation:0,youtubeUrl:"",youtubeId:"",title:"Video YouTube",color:"#ffffff",backgroundColor:"#0f172a"};t.elements.push(a),e.selectedElementId=a.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()}),c.querySelector("[data-add-link]").addEventListener("click",()=>{const t=b();if(!t)return;const a={id:E("element"),type:"link",x:90,y:300,width:260,height:70,rotation:0,text:"Buka tautan",url:"https://",linkStyle:"button",color:"#ffffff",backgroundColor:"#047857"};t.elements.push(a),e.selectedElementId=a.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()}),c.querySelector("[data-add-shape]").addEventListener("click",()=>{const t=b();if(!t)return;const a={id:E("element"),type:"shape",x:110,y:140,width:220,height:150,rotation:0,text:"Isi bentuk",shapeType:"rounded",borderRadius:24,fontSize:28,color:"#ffffff",backgroundColor:"#0f766e"};t.elements.push(a),e.selectedElementId=a.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()}),c.querySelector("[data-add-line]").addEventListener("click",()=>{const t=b();if(!t)return;const a={id:E("element"),type:"line",x:100,y:Math.max(70,Math.round(t.height/2)-20),width:Math.max(220,t.width-200),height:40,rotation:0,color:"#0f766e",backgroundColor:"transparent",strokeWidth:4,lineStyle:"solid",arrow:"none"};t.elements.push(a),e.selectedElementId=a.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()});const V=(t,a,r=0)=>{const i=b(),s=e.presentation.canvas,l=i?Number(i.x||0)+(Number(i.width||800)-t)/2:160,o=i?Number(i.y||0)-a-70+r:120+r;return{x:x(l,30,Math.max(30,Number(s.width||2400)-t-30),160),y:x(o,30,Math.max(30,Number(s.height||1400)-a-30),120)}};c.querySelector("[data-add-canvas-text]").addEventListener("click",()=>{const t=V(520,90),a={id:E("canvas-element"),type:"text",...t,width:520,height:90,rotation:0,text:"Tulis judul atau keterangan Overview",fontSize:36,color:"#ffffff",backgroundColor:"transparent",align:"center",bold:!0};e.presentation.canvas.elements.push(a),e.selectedCanvasElementId=a.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,w(),v(),u()}),c.querySelector("[data-add-canvas-line]").addEventListener("click",()=>{const t=V(440,40,125),a={id:E("canvas-element"),type:"line",...t,width:440,height:40,rotation:0,color:"#34d399",backgroundColor:"transparent",strokeWidth:5,lineStyle:"solid",arrow:"none"};e.presentation.canvas.elements.push(a),e.selectedCanvasElementId=a.id,e.selectedElementId=null,e.mode="overview",e.manualCamera=!1,w(),v(),u()}),c.querySelector("[data-add-image]").addEventListener("click",()=>{b()&&n.imageInput.click()}),c.querySelector("[data-add-logo]").addEventListener("click",()=>{b()&&n.logoInput.click()});const _=async(t,a)=>{const r=t.files?.[0],i=b();if(!r||!i)return;const s=new FormData;s.append("image",r),n.saveStatus.textContent="Mengunggah gambar...";try{const l=await fetch(c.dataset.uploadUrl,{method:"POST",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:s}),o=await l.json();if(!l.ok)throw new Error(o.message||"Gambar gagal diunggah.");e.presentation.assets[String(o.asset.id)]=o.asset;const y={id:E("element"),type:a,assetId:o.asset.id,x:90,y:80,width:Math.min(a==="logo"?220:420,i.width-180),height:Math.min(a==="logo"?220:280,i.height-140),rotation:0,alt:o.asset.name,fit:a==="logo"?"contain":"cover",shape:a==="logo"?"circle":"rounded",color:"#0f172a",backgroundColor:"transparent"};i.elements.push(y),e.selectedElementId=y.id,e.selectedCanvasElementId=null,e.mode="focus",e.manualCamera=!1,v(),u()}catch(l){n.saveStatus.textContent=l.message,n.saveStatus.classList.add("text-red-600")}finally{t.value=""}};n.imageInput.addEventListener("change",()=>_(n.imageInput,"image")),n.logoInput.addEventListener("change",()=>_(n.logoInput,"logo")),n.frameList.addEventListener("click",t=>{const a=t.target.closest("[data-frame-focus]");if(a){U(a.dataset.frameFocus);return}const r=t.target.closest("[data-frame-move]");if(!r)return;const i=e.presentation.canvas.frames,s=i.findIndex(o=>o.id===r.dataset.frameId),l=r.dataset.frameMove==="up"?s-1:s+1;s<0||l<0||l>=i.length||([i[s],i[l]]=[i[l],i[s]],v(),q())}),n.inspector.addEventListener("input",t=>{const a=t.target.closest("[data-inspector-prop]");if(!a)return;const r=a.closest("[data-inspector-scope]")?.dataset.inspectorScope,i=r==="frame"?b():r==="canvas-element"?W():K();if(!i||r==="frame"&&["width","height"].includes(a.dataset.inspectorProp))return;let s=a.type==="checkbox"?a.checked:a.value;["x","y","width","height","fontSize","borderRadius","strokeWidth","rotation"].includes(a.dataset.inspectorProp)&&(s=Number(s)),a.dataset.inspectorProp==="items"&&(s=String(s).split(`
`).map(l=>l.trim()).filter(Boolean).slice(0,8)),i[a.dataset.inspectorProp]=s,a.dataset.inspectorProp==="youtubeUrl"&&(i.youtubeId=fe(s)),(r==="frame"||r==="canvas-element")&&w(),v(`inspector:${r}:${i.id}:${a.dataset.inspectorProp}`),Q({stage:n.stage,presentation:e.presentation,selectedFrameId:e.selectedFrameId,selectedElementId:e.selectedElementId,selectedCanvasElementId:e.selectedCanvasElementId,overview:e.mode==="overview",editable:!0}),e.manualCamera?N():P(!1),r==="frame"&&a.dataset.inspectorProp==="title"&&q(),["shape","shapeType","diagramType"].includes(a.dataset.inspectorProp)&&F()}),n.inspector.addEventListener("change",t=>{const a=t.target.closest("[data-inspector-prop]"),r=a?.closest("[data-inspector-scope]")?.dataset.inspectorScope;if(!a||r!=="frame"||!["width","height"].includes(a.dataset.inspectorProp))return;const i=b();i&&(B(i,a.dataset.inspectorProp==="width"?Number(a.value):i.width,a.dataset.inspectorProp==="height"?Number(a.value):i.height),v(),u(!1))}),n.inspector.addEventListener("click",t=>{const a=t.target.closest("[data-frame-size]");if(a){const r=b(),[i,s]=a.dataset.frameSize.split("x").map(Number);B(r,i,s),v(),u(!1);return}if(t.target.closest("[data-focus-selected-frame]")){e.mode="focus",e.manualCamera=!1,u();return}if(t.target.closest("[data-delete-selected-element]")){if(t.target.closest("[data-inspector-scope]")?.dataset.inspectorScope==="canvas-element")e.presentation.canvas.elements=e.presentation.canvas.elements.filter(i=>i.id!==e.selectedCanvasElementId),e.selectedCanvasElementId=null;else{const i=b();i.elements=i.elements.filter(s=>s.id!==e.selectedElementId),e.selectedElementId=null}w(),v(),u(!1);return}if(t.target.closest("[data-delete-selected-frame]")&&e.presentation.canvas.frames.length>1){const r=e.presentation.canvas.frames,i=r.findIndex(s=>s.id===e.selectedFrameId);r.splice(i,1),e.selectedFrameId=r[Math.max(0,i-1)]?.id||r[0]?.id,e.selectedElementId=null,e.selectedCanvasElementId=null,e.mode="overview",e.manualCamera=!1,w(),v(),u()}});const J=t=>{e.presentation.title=n.title.value,e.presentation.description=n.description.value,e.presentation.backgroundColor=n.background.value,e.presentation.pathMode=n.pathMode.value,v(`metadata:${t.target.dataset.editorTitle!==void 0?"title":t.target.dataset.editorDescription!==void 0?"description":t.target.dataset.editorBackground!==void 0?"background":"path"}`),document.activeElement===n.background&&u(!1)};[n.title,n.description,n.background,n.pathMode].forEach(t=>{t.addEventListener("input",J),t.addEventListener("change",J)}),n.viewport.addEventListener("wheel",t=>{if(t.preventDefault(),t.ctrlKey||t.metaKey?e.cameraScale=le(n.viewport,n.stage,oe(t.deltaY),t.clientX,t.clientY,{minimumScale:.03,maximumScale:4}):e.cameraScale=de(n.stage,-t.deltaX,-t.deltaY),e.manualCamera=!0,(t.ctrlKey||t.metaKey)&&e.mode==="focus"&&e.cameraScale<=e.focusScale*.72){e.mode="overview",e.selectedElementId=null,u(!1);return}N()},{passive:!1});const te=()=>{const t=e.drag;t&&(t.kind==="frame-resize"?(t.target.width=t.originWidth,t.target.height=t.originHeight,t.frameElements.forEach(a=>{a.item.x=a.x,a.item.y=a.y,a.item.width=a.width,a.item.height=a.height,a.item.type==="text"&&(a.item.fontSize=a.fontSize)})):["element-resize","canvas-element-resize"].includes(t.kind)&&t.target?(t.target.x=t.originX,t.target.y=t.originY,t.target.width=t.originWidth,t.target.height=t.originHeight):t.target&&(t.target.x=t.originX,t.target.y=t.originY),e.drag=null,e.manualCamera=!0,u(!1))};L=ce(n.viewport,n.stage,{minimumScale:.03,maximumScale:4,onStart:()=>{e.overviewCandidate=!1,te()},onUpdate:t=>{e.cameraScale=t,e.manualCamera=!0,e.mode==="focus"&&t<=e.focusScale*.72&&(e.overviewCandidate=!0),N()},onEnd:()=>{!e.overviewCandidate||e.mode!=="focus"||(e.mode="overview",e.selectedElementId=null,e.manualCamera=!0,e.overviewCandidate=!1,u(!1))},onTap:t=>{if(e.mode!=="overview"||e.drag?.moved)return!1;const a=t.target.closest?.("[data-canvas-element-id]");if(a)return e.selectedCanvasElementId=a.dataset.canvasElementId,e.selectedElementId=null,e.manualCamera=!0,u(!1),!0;const r=t.target.closest?.("[data-frame-id]");return r?(U(r.dataset.frameId),!0):!1}}),n.viewport.addEventListener("pointerdown",t=>{if(t.button!==0||L?.isActive())return;e.cameraScale=ue(n.stage);const a=t.target.closest("[data-canvas-element-resize]"),r=t.target.closest(".pkg-presentation-element[data-canvas-element-id]"),i=a?.dataset.canvasElementId||r?.dataset.canvasElementId,s=e.mode==="overview"?e.presentation.canvas.elements.find(S=>S.id===i):null;if(s){const S=n.stage.querySelector(`.pkg-presentation-element[data-canvas-element-id="${CSS.escape(s.id)}"]`),ie=a?.closest(".pkg-presentation-element-controls")||n.stage.querySelector(`.pkg-presentation-element-controls[data-canvas-element-id="${CSS.escape(s.id)}"]`);e.selectedCanvasElementId=s.id,e.selectedElementId=null,e.drag={kind:a?"canvas-element-resize":"canvas-element",target:s,node:S,controlsNode:ie,frame:e.presentation.canvas,resizeDirection:a?.dataset.canvasElementResize||null,startX:t.clientX,startY:t.clientY,originX:Number(s.x||0),originY:Number(s.y||0),originWidth:Number(s.width||100),originHeight:Number(s.height||80),frameElements:[],moved:!1},n.viewport.setPointerCapture(t.pointerId),F();return}const l=t.target.closest("[data-frame-id]"),o=t.target.closest("[data-frame-resize]"),y=t.target.closest("[data-frame-drag]"),k=t.target.closest("[data-element-resize]");if(!l)return;const g=R(e.presentation,l.dataset.frameId),h=t.target.closest(".pkg-presentation-element[data-element-id]"),p=k?.dataset.elementId||h?.dataset.elementId,f=A(g,p),ae=f?l.querySelector(`.pkg-presentation-element[data-element-id="${CSS.escape(f.id)}"]`):null,re=k?.closest(".pkg-presentation-element-controls")||(f?l.querySelector(`.pkg-presentation-element-controls[data-element-id="${CSS.escape(f.id)}"]`):null);e.selectedFrameId=g.id,e.selectedElementId=e.mode==="focus"&&f?f.id:null,e.selectedCanvasElementId=null;const ne=(g.elements||[]).map(S=>({item:S,x:Number(S.x||0),y:Number(S.y||0),width:Number(S.width||100),height:Number(S.height||80),fontSize:Number(S.fontSize||32)}));e.drag={kind:e.mode==="overview"?o?"frame-resize":"frame":k?"element-resize":f?"element":null,target:e.mode==="overview"?g:f,node:e.mode==="overview"?l:ae,controlsNode:re,frame:e.mode==="overview"?e.presentation.canvas:g,explicitFrameDrag:!!y,resizeDirection:k?.dataset.elementResize||null,startX:t.clientX,startY:t.clientY,originX:e.mode==="overview"?g.x:f?.x,originY:e.mode==="overview"?g.y:f?.y,originWidth:e.mode==="overview"?g.width:f?.width,originHeight:e.mode==="overview"?g.height:f?.height,frameElements:ne,moved:!1},n.viewport.setPointerCapture(t.pointerId),F()}),n.viewport.addEventListener("pointermove",t=>{if(L?.isActive()||!e.drag?.kind||!e.drag.target)return;const a=t.clientX-e.drag.startX,r=t.clientY-e.drag.startY,i=e.drag.kind.includes("resize")?3:7;if(!e.drag.moved&&Math.hypot(a,r)<i)return;e.drag.moved=!0;const s=a/e.cameraScale,l=r/e.cameraScale;if(e.drag.kind==="frame-resize"){const o=x(e.drag.originWidth+s,320,1600,e.drag.originWidth),y=x(e.drag.originHeight+l,180,900,e.drag.originHeight),k=o/e.drag.originWidth,g=y/e.drag.originHeight,h=Math.min(k,g);e.drag.target.width=o,e.drag.target.height=y,e.drag.node.style.width=`${o}px`,e.drag.node.style.height=`${y}px`,e.drag.frameElements.forEach(p=>{p.item.x=p.x*k,p.item.y=p.y*g,p.item.width=Math.max(40,p.width*k),p.item.height=Math.max(30,p.height*g),p.item.type==="text"&&(p.item.fontSize=x(p.fontSize*h,10,160,p.fontSize));const f=e.drag.node.querySelector(`[data-element-id="${CSS.escape(p.item.id)}"]`);f&&(f.style.left=`${p.item.x}px`,f.style.top=`${p.item.y}px`,f.style.width=`${p.item.width}px`,f.style.height=`${p.item.height}px`,p.item.type==="text"&&(f.style.fontSize=`${p.item.fontSize}px`))})}else if(["element-resize","canvas-element-resize"].includes(e.drag.kind)){const o=e.drag.resizeDirection||"se",y=40,k=30;let g=e.drag.originX,h=e.drag.originY,p=e.drag.originWidth,f=e.drag.originHeight;o.includes("e")&&(p=x(e.drag.originWidth+s,y,Math.max(y,e.drag.frame.width-e.drag.originX),e.drag.originWidth)),o.includes("s")&&(f=x(e.drag.originHeight+l,k,Math.max(k,e.drag.frame.height-e.drag.originY),e.drag.originHeight)),o.includes("w")&&(g=x(e.drag.originX+s,0,e.drag.originX+e.drag.originWidth-y,e.drag.originX),p=e.drag.originWidth+(e.drag.originX-g)),o.includes("n")&&(h=x(e.drag.originY+l,0,e.drag.originY+e.drag.originHeight-k,e.drag.originY),f=e.drag.originHeight+(e.drag.originY-h)),Object.assign(e.drag.target,{x:g,y:h,width:p,height:f}),e.drag.node.style.left=`${g}px`,e.drag.node.style.top=`${h}px`,e.drag.node.style.width=`${p}px`,e.drag.node.style.height=`${f}px`,e.drag.controlsNode&&(e.drag.controlsNode.style.left=`${g}px`,e.drag.controlsNode.style.top=`${h}px`,e.drag.controlsNode.style.width=`${p}px`,e.drag.controlsNode.style.height=`${f}px`)}else{const o=Math.max(0,e.drag.frame.width-e.drag.target.width),y=Math.max(0,e.drag.frame.height-e.drag.target.height);e.drag.target.x=x(e.drag.originX+s,0,o,e.drag.originX),e.drag.target.y=x(e.drag.originY+l,0,y,e.drag.originY),e.drag.node.style.left=`${e.drag.target.x}px`,e.drag.node.style.top=`${e.drag.target.y}px`,e.drag.controlsNode&&(e.drag.controlsNode.style.left=`${e.drag.target.x}px`,e.drag.controlsNode.style.top=`${e.drag.target.y}px`)}});const Z=t=>{if(L?.shouldSuppressTap()){e.drag=null;return}if(!e.drag)return;const a=e.drag;if(e.drag=null,n.viewport.hasPointerCapture(t.pointerId)&&n.viewport.releasePointerCapture(t.pointerId),a.moved)w(),v(),u(!1);else if(e.mode==="overview"&&a.kind==="frame"&&!a.explicitFrameDrag){e.mode="focus",e.manualCamera=!1,u();return}else if(e.mode==="overview"&&["frame","canvas-element"].includes(a.kind)){u(!1);return}else if(e.mode==="focus"&&a.kind==="element"){u(!1);return}q()};n.viewport.addEventListener("pointerup",Z),n.viewport.addEventListener("pointercancel",Z);async function I(){if(window.clearTimeout(e.saveTimer),e.activeSave){const r=await e.activeSave;return r&&e.dirty?I():r}if(!e.dirty)return!0;const t=e.changeVersion;e.saving=!0,n.saveStatus.textContent="Menyimpan...",e.activeSave=(async()=>{try{w();const r=await fetch(c.dataset.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]')?.content||"",Accept:"application/json"},body:JSON.stringify({title:n.title.value,description:n.description.value,background_color:n.background.value,path_mode:n.pathMode.value,canvas_data:e.presentation.canvas})}),i=await r.json();if(!r.ok)throw new Error(i.message||Object.values(i.errors||{})[0]?.[0]||"Presentasi gagal disimpan.");return e.changeVersion===t&&(e.dirty=!1,n.saveStatus.textContent="Semua perubahan tersimpan",n.saveStatus.classList.remove("text-amber-600","dark:text-amber-300","text-red-600")),!0}catch(r){return n.saveStatus.textContent=r.message,n.saveStatus.classList.add("text-red-600"),!1}finally{e.saving=!1}})();const a=await e.activeSave;return e.activeSave=null,a&&e.dirty?I():a}n.undo.addEventListener("click",O),n.redo.addEventListener("click",X),document.addEventListener("keydown",t=>{if(!(t.ctrlKey||t.metaKey)||t.altKey)return;const a=t.key.toLowerCase();a==="z"?(t.preventDefault(),t.shiftKey?X():O()):a==="y"&&(t.preventDefault(),X())}),c.querySelector("[data-editor-save]").addEventListener("click",I),c.querySelectorAll("[data-export-link]").forEach(t=>{t.addEventListener("click",async a=>{if(!e.dirty)return;a.preventDefault(),await I()&&window.location.assign(t.href)})}),c.querySelectorAll("[data-save-before-open]").forEach(t=>{t.addEventListener("click",async a=>{if(!e.dirty)return;a.preventDefault();const r=t.target==="_blank"?window.open("about:blank","_blank"):null;await I()?r?r.location.href=t.href:window.location.assign(t.href):r&&r.close()})}),c.querySelectorAll("[data-publish-form]").forEach(t=>{t.addEventListener("submit",async a=>{e.dirty&&(a.preventDefault(),await I()&&t.submit())})}),window.addEventListener("beforeunload",t=>{e.dirty&&(t.preventDefault(),t.returnValue="")}),new ResizeObserver(()=>{e.manualCamera=!1,P(!1)}).observe(n.viewport),u(!1)}function $(m,e,n,L,M){return`<div><label class="form-label">${m}</label><input type="number" class="pkg-field w-full" min="${L}" max="${M}" data-inspector-prop="${e}" value="${Math.round(n)}"></div>`}function C(m,e,n){return`<div><label class="form-label">${m}</label><input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="${e}" value="${n}"></div>`}function d(m,e,n){return`<option value="${m}" ${m===n?"selected":""}>${e}</option>`}function H(m,e){return/^#[0-9a-f]{6}$/i.test(m||"")?m:e}function pe(m){return{text:"Elemen Teks",image:"Elemen Gambar",logo:"Elemen Logo",youtube:"Elemen YouTube",link:"Elemen Tautan",shape:"Elemen Bentuk",line:"Elemen Garis",diagram:"Elemen Diagram"}[m]||"Elemen"}function fe(m){return String(m||"").trim().match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/i)?.[1]||""}function Y(m){return String(m??"").replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"})[e])}function T(m){return Y(m).replace(/\n/g,"&#10;")}
