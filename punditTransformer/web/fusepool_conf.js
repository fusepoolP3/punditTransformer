var punditConfig = {
    vocabularies: ['/vocabulary'],
    useBasicRelations: false,
    modules: {
        'Fp3': {
            active: true,
            debug: true,
            label: 'Finish',
            link: '%%saveFromPunditUrl%%'
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
            active: true,
            limit: 99
        }

    },
    useTemplates: false,
    annotationServerBaseURL: 'http://staging.punditbrain.netseven.it:8182/annotationserver/',
    annotationServerVersion: 'v2'
}
