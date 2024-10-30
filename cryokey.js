//
// Copyright 2013, Authenticade LLC
// MIT License - See LICENSE file for details.
//

// Attempt a multi-factor authentication. We do the ticketing and
// then submit the standard form with the ticket ID.
function ck_multi(_url, _ip)
{
  var ck = new CryoKey(_url, _ip);

  ck.oncomplete = function(_url, _response)
  {
    var form = document.forms['loginform'];
    var action = form.getAttribute("action");
    if (/\?/.test(action))
      form.setAttribute("action", action + "&cktid=" + escape(_response.cktid));
    else
      form.setAttribute("action", action + "?cktid=" + escape(_response.cktid));
    form.submit();
  };

  ck.initiate();
}

// Attempt basic authentication.
function ck_initiate(_url, _ip, _pass)
{
  var ck = new CryoKey(_url, _ip);

  ck.initiate(_pass);
}

