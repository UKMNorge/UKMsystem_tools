jQuery(document).ready(() => {
    var workList = new UKMresources.workQueue(
        'cleanList', {
            filterCountData: function(data) {
                return data.action;
            },
            elementHandler: function(id) {
                var emitter = new UKMresources.emitter(id);

                UKMresources.Request({
                    action: 'UKMsystem_tools_ajax',
                    controller: 'createKommuneFylkePage',
                    module: 'season',
                    containers: {
                        loading: '#' + id + ' .loading',
                        fatalError: '#status',
                        main: '#workList'
                    },
                    handleSuccess: (response) => {
                        emitter.emit('success', response);
                    },
                    handleError: (error, response) => {
                        emitter.emit('error', [error, response]);
                    }
                }).do({
                    type: jQuery('#' + id).attr('data-type'),
                    id: jQuery('#' + id).attr('data-id')
                });

                return emitter;
            }
        }
    );

    workList.on('success', (data) => {
        jQuery('#' + data.selector)
            .addClass('alert-' + data.color)
            .html(
                twigJS_seasoncreateblog.render(data)
            )
            .appendTo('#cleanedList');
    });

    workList.on('error', (data) => {
        jQuery('#' + data.selector)
            .addClass('alert-danger')
            .html(
                twigJS_seasoncreateblog.render(data)
            );
    });

    workList.on('status_update', (statuses, index) => {
        jQuery('#status').html(
            '<p id="total_status">Gjennomgår kommune/fylke ' + index + ' av ' + workList.length + '</p>'
        );
        statuses.forEach((count, id) => {
            jQuery('#status').append(id + ': ' + count + ' <br />');
        });
    });

    workList.on('done', (index, total) => {
        jQuery('#status').removeClass('alert-info').addClass('alert-success');
        jQuery('#total_status').html(
            'Ferdig! Har nå gjennomgått ' + index + ' av ' + total + ' kommuner, fylker og bydeler (i Oslo)'
        );
        jQuery('#toClean').hide();
    });

    jQuery('#cleanList li').each((index, site) => {
        workList.push(jQuery(site).attr('id'));
    });
    jQuery('#status').html(workList.length + ' kommuner og fylker skal gjennomgås').slideDown();

    workList.start();
});