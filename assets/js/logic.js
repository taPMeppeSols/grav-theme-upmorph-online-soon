/** 
	taPMeppe solutions (17.12.2019)
	inspired by the project 'eventually' @see https://html5up.net/eventually
*/

const UPMORPH_ACTIVE = 'active';

/**
 * PM (17.12.2019) this function is used to start the slider
 * @param {string} query the DOM query leading to all the slides
 * @param {number} duration the duration of each slide
 */
((query, duration) => {
	const 
		slides = document.querySelectorAll(query),
		len = slides.length

	setTimeout( //PM (24.01.2020) lazy loading
		query => {
			document.querySelectorAll(query +'[data-image-url]').forEach(slide => {
				slide.style.backgroundImage = `url('${slide.getAttribute('data-image-url')}')`
			})
		},
		0,
		query
	)

	if(len > 1){
		let i = iPrev = 0
		slides.item(i).classList.add('visible', 'top')
		setInterval(
			_ => {
				iPrev = i
				i = ++i % len
				//console.log(iPrev, i)

				//switch the top image
				const classList = slides.item(iPrev).classList
				classList.remove('top')
				slides.item(i).classList.add('visible', 'top')

				//hide the previous image after a short duration
				setTimeout(classList => classList.remove('visible'), duration / 2, classList)
			},
			duration
		)
	}
})('#slider div.slide', 6000);

/**
 * PM (20.01.2020) this function is used to set the modal handler
 * @param {string} query the body query
 */
(query => {
	const body = document.body || document.querySelector(query)
	if(body) body.addEventListener(
		'click',
		event => {
			const elt = event.target
			if(elt.matches('[data-target]')){
				const target = body.querySelector('#'+ elt.getAttribute('data-target'))
				if(target){
					//target.scrollTop = 0 //PM (08.02.2020) for the time being turn it off
					//target.scrollTo(0, 0) //scroll to the top -> doesn't work in EDGE
					target.classList.toggle(UPMORPH_ACTIVE) //add or remove
				}
			}
		}
	)
})('body');

/**
 * PM (20.01.2020) this function is used to set the form handler
 * @param {string} id the form id
 */
(id => {
	const form = document.getElementById(id)
	if(form){
		const 
			button = form.querySelector('button'),
			privacy = form.querySelector('input.privacy-policy'),
			address = form.querySelector('input.email'),
			notif = 'section.notification.'
		;
		if(button){
			if(privacy) privacy.addEventListener('click', _ => button.disabled = !privacy.checked)
			//DO NOT MERGE
			if(address){
				address.addEventListener(
					'focus',
					_ => document.querySelectorAll(notif + UPMORPH_ACTIVE).forEach(elt => elt.classList.remove(UPMORPH_ACTIVE))
				)
	
				button.addEventListener(
					'click', 
					function(){ //submit handler
						const email = address.value.trim()
						if(email.match(/^[\w.%+-]+@[\w.-]+\.[A-Za-z]{2,}$/)){
							//disable the form elements
							const 
								button = this, //required to enable access to the element in the anonymous functions used below
								progress = document.querySelector(notif +'progress').classList
							button.disabled = true
							address.disabled = true
							progress.add(UPMORPH_ACTIVE)
	
							const 
								http = new XMLHttpRequest(),
								regex = /\W+/g
							;
							http.open("POST", '', true)
							http.setRequestHeader( 
								'auth-code', 
								btoa(
									btoa(location.origin.replace(regex, '#')).replace(regex, '@')
								).replace(regex, '')
							)
							http.onreadystatechange = function(){
								if(this.readyState == XMLHttpRequest.DONE){ //TRUE once the client receives the server response
									//DO NOT MERGE
									if(this.status == 200){
										progress.remove(UPMORPH_ACTIVE)
										if(this.responseText == 'success') address.value = '';
										document.querySelector(notif + this.responseText).classList.add(UPMORPH_ACTIVE)
	
										if(UPMORPH.dev) console.log(this.responseText)
									}else document.querySelector(notif +'error').classList.add(UPMORPH_ACTIVE)
	
									//re-enable the form elements
									button.disabled = false
									address.disabled = false
								}
							};
							
							const data = new FormData()
							data.append(
								UPMORPH.auth, 
								btoa(
									btoa(location.href.replace(regex, '#')).replace(regex, '@')
								).replace(regex, '')
							)
							data.append('name', form.querySelector('input.name').value) //no trim on purpose
							data.append('email', email)
							http.send(data)
						}else address.focus()
					}
				)
			}
		}
	}
})('registration')