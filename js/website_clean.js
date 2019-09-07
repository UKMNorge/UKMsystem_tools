$(document).ready(() => {
    var workList = new UKMresources.workQueue(
        'cleanList',
        {
            filterCountData: function( data ) {
                return data.action;
            },
            elementHandler: function (blog_id) {
                var emitter = new UKMresources.emitter('blog_' + blog_id);

                var ControlWebsite = UKMresources.Request({
                    action: 'UKMsystem_tools_ajax',
                    controller: 'controlBlog',
                    module: 'season',
                    containers: {
                        loading: '#' + blog_id + ' .loading',
                        success: '#' + blog_id + ' .success',
                        error: '#' + blog_id + ' .error',
                        fatalError: '#status',
                        main: '#formContainer'
                    },
                    handleSuccess: (response) => {
                        emitter.emit('success', response);
                    },
                    handleError: (response) => {
                        emitter.emit('error', response);
                    }
                });

                ControlWebsite.do(
                    {
                        blog_id: blog_id
                    }
                );

                return emitter;
            }
        }
    );

    workList.on('success', (data) => {
        $('#blog_'+ data.POST.blog_id)
            .addClass('alert-'+ data.color)
            .html(
                twigJS_seasoncleanblog.render(data)
            )
            .appendTo('#cleanedList');
    });

    workList.on('error', (data) => {
        $('#blog_'+ response.POST.blog_id)
            .addClass('alert-danger')
            .html(
                twigJS_seasoncleanblog.render(response)
            );
    });

    workList.on('status_update', (statuses, index) => {
        $('#status').html(
            '<p id="total_status">Gjennomgår side '+ index +' av '+ workList.length +'</p>');

        statuses.forEach( (count, id) => {
            $('#status').append( id +': '+ count +' <br />');
        });

    });

    workList.on('done', (index,total) => {
        $('#status').removeClass('alert-info').addClass('alert-success');
            $('#total_status').html(
                'Ferdig! Har nå gjennomgått '+ index +' av '+ total +' sider'
            );
            $('#toClean').hide();
    });

    $('#cleanList li').each((index, site) => {
        workList.push($(site).attr('data-id'));
    });
    $('#status').html(workList.length + ' sider skal gjennomgås').slideDown();

    workList.on('done', () => {
        
    });
    workList.start();
});