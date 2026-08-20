"use strict";
(function(){
  const ua=navigator.userAgent||"";
  const mobileHint=navigator.userAgentData?.mobile;
  const iPadOS=navigator.platform==="MacIntel"&&navigator.maxTouchPoints>1;
  const mobile=typeof mobileHint==="boolean"?mobileHint:/Android|iPhone|iPad|iPod|Mobile/i.test(ua)||iPadOS;
  window.isMobileDevice=()=>mobile;
  document.documentElement.classList.add(mobile?"mobile-device":"desktop-device");
})();
