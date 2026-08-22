"use strict";
const dateInput=document.getElementById("dateInput");
let datePart=0;
dateInput.addEventListener("focus",()=>{datePart=0});
document.addEventListener("keydown",event=>{
  if(event.key==="Tab"&&!event.shiftKey&&event.target.id==="dateInput"){
    datePart+=1;
    if(datePart>=3){
      event.preventDefault();
      document.getElementById("locationInput").focus();
    }
    return;
  }
  if(event.key!=="Enter"||!matchMedia("(min-width: 761px)").matches)return;
  if(document.getElementById("recordDialog").open||event.target.closest("input,select,button,a"))return;
  event.preventDefault();
  document.getElementById("addButton").click();
});
document.getElementById("locationInput").addEventListener("input",refreshLocations);
document.getElementById("togglePaidSelected").addEventListener("click",toggleSelectedPaid);

const recordsWrap=document.getElementById("recordsWrap");
const floatingScrollbar=document.createElement("div");
floatingScrollbar.className="floating-record-scrollbar";
floatingScrollbar.setAttribute("aria-hidden","true");
floatingScrollbar.innerHTML="<div></div>";
document.body.appendChild(floatingScrollbar);
let syncingScroll=false;
function updateFloatingScrollbar(){
  const rect=recordsWrap.getBoundingClientRect();
  const needed=matchMedia("(min-width: 761px)").matches&&!recordsWrap.hidden&&recordsWrap.scrollWidth>recordsWrap.clientWidth&&rect.top<innerHeight&&rect.bottom>innerHeight;
  floatingScrollbar.hidden=!needed;
  if(!needed)return;
  floatingScrollbar.style.left=`${Math.max(0,rect.left)}px`;
  floatingScrollbar.style.width=`${Math.min(innerWidth,rect.right)-Math.max(0,rect.left)}px`;
  floatingScrollbar.firstElementChild.style.width=`${recordsWrap.scrollWidth}px`;
  if(!syncingScroll)floatingScrollbar.scrollLeft=recordsWrap.scrollLeft;
}
recordsWrap.addEventListener("scroll",()=>{syncingScroll=true;floatingScrollbar.scrollLeft=recordsWrap.scrollLeft;syncingScroll=false});
floatingScrollbar.addEventListener("scroll",()=>{if(syncingScroll)return;syncingScroll=true;recordsWrap.scrollLeft=floatingScrollbar.scrollLeft;syncingScroll=false});
addEventListener("resize",updateFloatingScrollbar);
addEventListener("scroll",updateFloatingScrollbar,{passive:true});
new MutationObserver(updateFloatingScrollbar).observe(recordsWrap,{attributes:true,childList:true,subtree:true});
