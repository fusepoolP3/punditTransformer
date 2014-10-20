var punditConfig = {
    vocabularies: ['/vocabulary'],
    useBasicRelations: false,
    modules: {
	'Fp3': {
	    active: true,
	    label: 'Finish',
	    link: 'http://punditTransformer.netseven.it/save_from_pundit'
	},
	'Client': {
            active: true
        }
    },
    useTemplates: false,

    annotationServerBaseURL: 'http://demo-cloud.as.thepund.it:8080/annotationserver/'

}