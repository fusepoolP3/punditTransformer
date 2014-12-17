var punditConfig = {
    vocabularies: ['/vocabulary'],
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
        'FreebaseSelector': {
            active: true
        },
        'DbpediaSelector': {
            active: true
        }

    },
    useTemplates: false,
    annotationServerBaseURL: 'http://demo-cloud.as.thepund.it:8080/annotationserver/'
}