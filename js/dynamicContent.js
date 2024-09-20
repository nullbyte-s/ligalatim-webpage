function carregarPagina(pagina, scripts = [], estilos = []) {
	return new Promise((resolve, reject) => {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function () {
			if (this.readyState == 4) {
				if (this.status == 200) {
					document.getElementById("content").innerHTML = this.responseText;
					window.scrollTo(0, 0);

					function carregarScript(src) {
						return new Promise((resolve, reject) => {
							let script = document.createElement('script');
							script.src = 'js/' + src;
							script.onload = () => resolve();
							script.onerror = () => reject(new Error(`Erro ao carregar o script: ${src}`));
							document.head.appendChild(script);
						});
					}

					function carregarEstilo(src) {
						return new Promise((resolve, reject) => {
							let link = document.createElement('link');
							link.rel = 'stylesheet';
							link.href = 'css/' + src;
							link.onload = () => resolve();
							link.onerror = () => reject(new Error(`Erro ao carregar o estilo: ${href}`));
							document.head.appendChild(link);
						});
					}


					Promise.all([
						...estilos.map(carregarEstilo),
						...scripts.map(carregarScript)
					])
						.then(() => {
							$(document).ready(function () {
								var zindex = 10;
								$("div.card").click(function (e) {
									e.preventDefault();
									var isShowing = false;

									if ($(this).hasClass("show")) {
										isShowing = true;
									}
									if ($("div.cards").hasClass("showing")) {
										$("div.card.show")
											.removeClass("show");
										if (isShowing) {
											$("div.cards")
												.removeClass("showing");
										} else {
											$(this)
												.css({ zIndex: zindex })
												.addClass("show");
										}
										zindex++;
									} else {
										$("div.cards")
											.addClass("showing");
										$(this)
											.css({ zIndex: zindex })
											.addClass("show");
										zindex++;
									}
								});
							});

							resolve();
						})
						.catch(reject);

				} else {
					reject(new Error(`Erro ao carregar a página: ${pagina}`));
				}
			}
		};

		xhttp.open("GET", "includes/" + pagina, true);
		xhttp.send();
	});
}