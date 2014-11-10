var punditConfig = {
    vocabularies: ['/vocabulary'],
    //vocabularies: ['http://manager.korbo.org/91'],
    useBasicRelations: false,
    modules: {
	'Fp3': {
	    active: true,
        debug: true,
	    label: 'Finish',
	    link: 'http://punditTransformer.local/save_from_pundit'
        },
        'Client': {
                active: true
        },
        'XpointersHelper': {
            active: true,
            debug: true
        },
        'Korbo2Selector':{
            active: false
        },
        'KorboBasketSelector':{
            active: false
        },
        'MurucaSelector':{
            active: false
        }

    },

    useTemplates: false,

    annotationServerBaseURL: 'http://demo-cloud.as.thepund.it:8080/annotationserver/'

}