/*!
 * Expresso Lite
 * Main script to login page.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2016 Serpro (http://www.serpro.gov.br)
 */

require([
    'common-js/jQuery',
    'common-js/App',
    'common-js/Cordova',
    'common-js/SplashScreen'
],
function($, App, Cordova, SplashScreen) {
    window.Cache = {
        splashScreen: null
    };

    App.ready(function() {
        Cache.splashScreen = new SplashScreen();

        $.when( // using $.when just to be consistent with other modules
            Cache.splashScreen.load()
        ).done(function() {
            if (Cordova) {
                Cache.splashScreen.showThrobber();
                if (Cordova.HasInternetConnection()) {
                    CheckSessionStatus();
                } else {
                    HandlePhoneWithoutInternet();
                }
            } else {
                var isBrowserValid = ValidateBrowserMinimumVersion([ // warning: the list order matters
                    { name:'SamsungBrowser', version:4 }, // Samsung Android browser
                    { name:'Firefox', version:38 },
                    { name:'CriOS', version:34 }, // Chrome on iOS
                    { name:'Version', version:7 }, // Safari
                    { name:'Chrome', version:34 }
                ]);

                if (!isBrowserValid) {
                    $('#main-screen,#unsupportedMsg').show();
                    $('#frmLogin').hide();
                } else {
                    CheckSessionStatus();
                }
            }
        });
    });

    function CheckSessionStatus() {
        App.post('checkSessionStatus')
        .done(function(response) {
            if (response.status == 'active') {
                App.goToFolder('./mail'); // there is an active session, go to mail module
            } else {
                if (Cordova) {
                    // no active session, but since we have Cordova
                    // we may have the credentials to start a new session
                    Cordova.GetCurrentAccount().done(function(account) {
                        if (account != null) {
                            TryImplicitLogin(account.login, account.password);
                        } else {
                            Init(); //no credential found, proceed with usual init
                        }
                    }).fail(function (error) {
                        // This should not happen, but in case something goes wrong,
                        // at least we'll have some information to work on
                        App.errorMessage('Ocorreu um erro ao tentar localizar as credenciais do usuário.\n' +
                            'Realize o login novamente.');
                        console.log('Cordova.GetCurrentAccount failed: ' + error);
                        Init();
                    });
                } else {
                    Init();
                }
            }
        }).fail(function(error) {
            window.alert('Ocorreu um erro ao realizar a conexão com o Expresso.\n'+
                'É possível que o sistema esteja fora do ar.');
        });
    }

    function HandlePhoneWithoutInternet() {
        // For now, the only thing we can do is to show a message for the user.
        // However, once we have e-mail data stored for offline access, we will
        // be able to redirect to mail module to show that data (only if the
        // user has a stored credential, of course)

        Cache.splashScreen.showNoInternetMessage();
    }

    function TryImplicitLogin(user, pwd) {
        function CordovaLoginFailed() {
            window.alert('Não foi possível se reconectar ao Expresso com as credencias armazenadas.\n' +
                'É necessário realizar o login novamente.');
            Init();
        }

        // Use the credential to perform an implicit login.
        // This will be transparent to the user,
        // since no fields are yet being displayed on screen
        App.post('login', {
            user: user,
            pwd: pwd
        })
        .fail(CordovaLoginFailed)
        .done(function(response) {
            if (!response.success) {
                CordovaLoginFailed();
            } else {
                //since the only visible thing right now is the splash screen,
                //just go straight to the mail module, without any fancy animations
                App.goToFolder('./mail');
            }
        });
    }

    function Init() {
        function ShowScreen() {
            $('#main-screen').velocity('fadeIn', {
                duration: 300,
                complete: function() {
                    LoadServerStatus(); // async
                    $('#user').focus();
                    $('#frmLogin').submit(DoLogin);
                    $('#frmChangePwd').submit(DoChangePassword);
                }
            });
        }

        if (Cordova) {
            Cache.splashScreen.moveUpAndClose().done(ShowScreen);
        } else {
            var user = App.getCookie('user');
            if (user !== null) {
                $('#user').val(user);
            }

            if (location.href.indexOf('#') !== -1)
                history.pushState('', '', location.pathname);

            ShowScreen();
        }
    }

    function ValidateBrowserMinimumVersion(allowedBrowsers) {
        var ua = navigator.userAgent;
        for (var i = 0; i < allowedBrowsers.length; ++i) {
            var navData = ua.match(new RegExp(allowedBrowsers[i].name+'/(\\d+)\\.'));
            if (navData !== null) {
                return parseInt(navData[1]) >= allowedBrowsers[i].version;
            }
        }
        return false;
    }

    function LoadServerStatus() {
        App.post('getAllRegistryData')
        .fail(function(resp) {
            window.alert('Erro ao consultar a versão atual do Expresso.\n'+
                'É possível que o Expresso esteja fora do ar.');
        }).done(function(data) {
            $('#classicHref').attr('href', data.liteConfig.classicUrl);
            $('#versionInfo').append(
                data.liteConfig.packageString+'<br/>'+
                data.Tinebase.version.packageString
            );

            // Android and iOS badges are shown only if the respective apps
            // are currently available at Play & Apple store.
            if (data.liteConfig.androidUrl === '' || data.liteConfig.androidUrl === undefined) {
                $('#androidBadge').remove();
            } else {
                $('#androidBadge').attr('href', data.liteConfig.androidUrl);
            }

            if (data.liteConfig.iosUrl === '' || data.liteConfig.iosUrl === undefined) {
                $('#iosBadge').remove();
            } else {
                $('#iosBadge').attr('href', data.liteConfig.iosUrl);
            }

            $('#externalLinks,#versionInfo').velocity('fadeIn', { duration:400 });
        });
    }

    function DoLogin() {
        function ValidateLogin() {
            if ($('#user').val() == '') {
                window.alert('Por favor, digite seu nome de usuário.');
                $('#user').focus();
                return false;
            } else if ($('#pwd').val() == '') {
                window.alert('Por favor, digite sua senha.');
                $('#pwd').focus();
                return false;
            }
            return true;
        }

        if (!ValidateLogin()) return false;
        $('#universalAccess,#externalLinks').velocity('fadeOut', { duration:200 });
        $('#btnLogin').hide();
        $('#frmLogin input').prop('disabled', true);
        $('#frmLogin .throbber').show().children('span').text('Efetuando login...');

        function RestoreLoginState() {
            if (!App.isPhone()) $('#universalAccess').show();
            $('#externalLinks').show();
            $('#btnLogin').show();
            $('#frmLogin input').prop('disabled', false);
            $('#frmLogin .throbber').hide();
            $('#user').focus();
        }

        App.post('login', {
            user:$('#user').val(),
            pwd:$('#pwd').val(),
            captcha: $('#captcha').val()
        }).fail(function(resp) {
            window.alert('Não foi possível efetuar login.\n' +
                'O usuário ou a senha estão incorretos.');
            RestoreLoginState();
        }).done(function(response) {
            if (response.expired) {
                RestoreLoginState();
                window.alert('Sua senha expirou, é necessário trocá-la.');
                var $frmLogin = $('#frmLogin').replaceWith($('#frmChangePwd')).appendTo('#templates');
                $('#cpNewPwd').focus();
            } else if (response.captcha) {
                window.alert('Número máximo de tentativas excedido.\n' +
                             'Informe também o CAPTCHA para efetuar o login');
                if (!$('#captchaDiv').is(':visible')) {
                    $('#captchaDiv').insertAfter('#pwd');
                }
                $('#captchaImg').attr('src', 'data:image/png;base64,' + response.captcha);
                RestoreLoginState();
            } else if (!response.success) {
                window.alert('Não foi possível efetuar login.\n' +
                    'O usuário ou a senha estão incorretos.');
                RestoreLoginState();
            } else {
                for (var i in response.userInfo) {
                    App.setUserInfo(i, response.userInfo[i]);
                }
                App.setCookie('user', $('#user').val(), 30); // store for 30 days

                if (Cordova) {
                    Cordova.SaveAccount($('#user').val(), $('#pwd').val())
                    .fail(function() {
                        console.log('O login foi bem sucedido, mas não foi possível armazenar as credencias do usuário neste dispositivo.');
                    })
                    .always(RedirectToMailModule);
                } else {
                    RedirectToMailModule();
                }
            }
        });
        return false;
    }

    function DoChangePassword() {
        if ($('#cpNewPwd').val() !== $('#cpNewPwd2').val()) {
            window.alert('As novas senha não coincidem, por favor digite-as novamente.');
            $('#cpNewPwd,#cpNewPwd2').val('');
            $('#cpNewPwd').focus();
        } else {
            $('#btnNewPwd').hide();
            $('#frmChangePwd input').prop('disabled', true);
            $('#frmChangePwd .throbber').show().children('span').text('Trocando senha...');

            function UglyTineFormatMsg(errorMessage) {
                // Ugly formatting function copied straight from Tine source.
                var title  = errorMessage.substr(0, (errorMessage.indexOf(':') + 1));
                var errorFull = errorMessage.substr((errorMessage.indexOf(':') + 1));
                var errorTitle = errorFull.substr(0, (errorFull.indexOf(':') + 1));
                var errors = errorFull.substr((errorFull.indexOf(':') + 1));
                return errorTitle+'\n\n'+errors.replace(/, /g, '\n').replace(/^\s/, '');
            }

            function RestoreChangePasswordState() {
                $('#btnNewPwd').show();
                $('#frmChangePwd input').prop('disabled', false);
                $('#frmChangePwd .throbber').hide();
                $('#cpNewPwd,#cpNewPwd2').val('');
                $('#cpNewPwd').focus();
            }

            App.post('changeExpiredPassword', {
                userName: $('#user').val(), // from login form
                oldPassword: $('#cpOldPwd').val(),
                newPassword: $('#cpNewPwd').val()
            }).fail(function(resp) {
                window.alert(UglyTineFormatMsg(resp.responseText));
                RestoreChangePasswordState();
            }).done(function(data) {
                window.alert('Senha alterada com sucesso.\nEfetue login com sua nova senha.');
                location.reload();
            });
        }
        return false;
    }

    function RedirectToMailModule() {
        $(document.body).css('overflow', 'hidden');
        $('#versionInfo, #frmLogin .throbber').hide();

        if (App.isPhone()) {
            var animTime = 300;
            $('#credent').css({
                    position: 'fixed',
                    left: $('#credent').offset().left,
                    top: $('#credent').offset().top
                })
                .appendTo(document.body)
                .velocity({ left:-$(window).width() }, { duration:animTime, queue:false });
            $('#frmLogin').css({
                    position: 'fixed',
                    display: 'block',
                    left: $('#logo-top').offset().left,
                    top: $('#frmLogin').offset().top
                })
                .appendTo(document.body)
                .velocity({ left:$(window).width() }, {
                    duration: animTime,
                    queue: false,
                    complete: function() {
                        App.goToFolder('./mail');
                    }
                });
        } else {
            var animTime = 600;
            $('#topgray').velocity({ opacity:0 }, { duration:animTime, queue:false });
            $('#thebg').velocity({ left:-$(window).width(), opacity:0 }, { duration:animTime, queue:false });
            $('#credent').velocity({ top:$(window).height() }, {
                duration: animTime,
                queue: false,
                complete: function() {
                    App.goToFolder('./mail');
                }
            });
        }
    }
});
