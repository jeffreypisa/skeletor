export function cta() {
  const strookCta = document.querySelector(".strook-cta");
  if (!strookCta) return;
  
  const prevSection = strookCta.previousElementSibling;
  const nextSection = strookCta.nextElementSibling;
  
  const topDiv = strookCta.querySelector(".js-backgroundcolor-top");
  const bottomDiv = strookCta.querySelector(".js-backgroundcolor-bottom");
  
  function getBgClass(element) {
      if (!element) return "";
      return [...element.classList].find(cls => cls.startsWith("bg-")) || "";
  }
  
  const topBgClass = getBgClass(prevSection);
  const bottomBgClass = getBgClass(nextSection);
  
  if (topDiv && topBgClass) topDiv.classList.add(topBgClass);
  if (bottomDiv && bottomBgClass) bottomDiv.classList.add(bottomBgClass);
}