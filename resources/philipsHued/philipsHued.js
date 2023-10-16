/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
var Jeedom = require('./jeedom/jeedom.js');
var EventSource = require('eventsource');
const https = require('https');
const fs = require('fs');
const args = Jeedom.getArgs()

if(typeof args.loglevel == 'undefined'){
args.loglevel = 'debug';
}
Jeedom.log.setLevel(args.loglevel)
Jeedom.log.info('Start philipsHued')
Jeedom.log.info('Log level on  : '+args.loglevel)
Jeedom.log.info('PID file : '+args.pid)
Jeedom.log.info('Apikey : '+args.apikey)
Jeedom.log.info('Callback : '+args.callback)
Jeedom.log.info('Bridges : '+args.bridges)
Jeedom.write_pid(args.pid)
Jeedom.com.config(args.apikey,args.callback,0)
Jeedom.com.test()

var bridges = JSON.parse(args.bridges);

for (i in bridges) {
  Jeedom.log.debug('Launch listenner for : '+bridges[i]['ip']);
  initGeolocClient(i)
  launchConnection(i,0)
}

function launchConnection(_bridge_id,_retry){
  if(_retry > 10){
    Jeedom.log.error('[launchConnection] Too much retry, I will kill me...')
    process.exit()
  }
  Jeedom.log.debug("[launchConnection] Launch SSE on bridge "+_bridge_id+', retry : '+_retry);
  bridges[_bridge_id]['es'] = new EventSource('https://'+bridges[_bridge_id]['ip']+'/eventstream/clip/v2',{
    headers:{
      'hue-application-key': bridges[_bridge_id]['key'],
      'Connection':'keep-alive',
      'Accept':'text/event-stream',
      'Cache-Control':'no-cache'
    },
    https: {
      rejectUnauthorized: false
    }
  });
  bridges[_bridge_id]['es'].onerror = function (err) {
    Jeedom.log.error('[launchConnection] Error on connextion to bridge : '+JSON.stringify(err)+'. Retry number '+_retry+'...')
    setTimeout(function(){
      launchConnection(_bridge_id,(_retry+1))
    },1000)
  };
  bridges[_bridge_id]['es'].addEventListener('message', function (e) {
    if(_retry != 0){
      _retry = 0;
    }
    bridges[_bridge_id]['lastMessage'] = Math.floor(new Date().getTime() / 1000)
    Jeedom.com.add_changes('bridge::'+_bridge_id,e.data);
  })

  if(bridges[_bridge_id]['keepalive']){
    clearInterval(bridges[_bridge_id]['keepalive']);
  }

  bridges[_bridge_id]['keepalive'] = setInterval(function(){
    if((bridges[_bridge_id]['lastMessage']+61) > Math.floor(new Date().getTime() / 1000)){
      return
    }
    Jeedom.log.error('[launchConnection] Lost connection to SSE server. Try reconnect...')
    bridges[_bridge_id]['es'] = new EventSource('https://'+bridges[_bridge_id]['ip']+'/eventstream/clip/v2',{
      headers:{
        'hue-application-key': bridges[_bridge_id]['key'],
        'Connection':'keep-alive',
        'Accept':'text/event-stream',
        'Cache-Control':'no-cache'
      },
      https: {
        rejectUnauthorized: false
      }
    });
    Jeedom.com.add_changes('bridge::'+_bridge_id,'resync');
  },30000);
}

function initGeolocClient(_bridge_id){
  let options = {
    host: bridges[_bridge_id]['ip'],
    port: 443,
    path: '/clip/v2/resource/geofence_client',
    method: 'GET',
    rejectUnauthorized: false,
    headers:{
      'hue-application-key': bridges[_bridge_id]['key'],
      'Connection':'keep-alive',
      'Accept':'text/event-stream',
      'Cache-Control':'no-cache'
    }
  };
  let req = https.request(options, function(res) {
    res.setEncoding('utf8');
    let body = '';
    res.on('data', function (chunk) {
      body = body + chunk;
    });
    res.on('end',function(){
      let datas = JSON.parse(body)
      Jeedom.log.debug("[initGeolocClient] Body :" + body);
      let jeedom_geoloc = null;
      for(let i in datas['data']){
        if(datas['data'][i].name.indexOf('jeedom') === 0){
          jeedom_geoloc = datas['data'][i]
        }
      }
      if(jeedom_geoloc == null){
        Jeedom.log.debug('[initGeolocClient] No jeedom user found need to create it');
        createJeedomGeoloc(_bridge_id)
        return;
      }
      Jeedom.log.debug('[initGeolocClient] Jeedom user found : '+JSON.stringify(jeedom_geoloc));
      bridges[_bridge_id]['geoloc_id'] = jeedom_geoloc.id
      updateJeedomGeolocName(_bridge_id);
    });
  });
  req.end();
}

function createJeedomGeoloc(_bridge_id){
  let options = {
    host: bridges[_bridge_id]['ip'],
    port: 443,
    path: '/clip/v2/resource/geofence_client',
    method: 'POST',
    rejectUnauthorized: false,
    headers:{
      'hue-application-key': bridges[_bridge_id]['key'],
      'Connection':'keep-alive',
      'Accept':'text/event-stream',
      'Cache-Control':'no-cache'
    }
  };
  let req = https.request(options, function(res) {
    res.setEncoding('utf8');
    let body = '';
    res.on('data', function (chunk) {
      body = body + chunk;
    });
    res.on('end',function(){
      Jeedom.log.debug("[createJeedomGeoloc] Body :" + body);
      initGeolocClient(_bridge_id)
    });
  });
  req.write(JSON.stringify({
    'type' : 'geofence_client',
    'is_at_home' : true,
    'name' : 'jeedom'
  }));
  req.end();
}

function updateJeedomGeolocName(_bridge_id){
  Jeedom.log.debug("[updateJeedomGeolocName] Start keepalive");
  let options = {
    host: bridges[_bridge_id]['ip'],
    port: 443,
    path: '/clip/v2/resource/geofence_client/'+bridges[_bridge_id]['geoloc_id'],
    method: 'PUT',
    rejectUnauthorized: false,
    headers:{
      'hue-application-key': bridges[_bridge_id]['key'],
      'Connection':'keep-alive',
      'Accept':'text/event-stream',
      'Cache-Control':'no-cache'
    }
  };
  setInterval(function(){
    if((bridges[_bridge_id]['lastMessage']+30) > Math.floor(new Date().getTime() / 1000)){
      return;
    }
    let req = https.request(options, function(res) {
      res.setEncoding('utf8');
      let body = '';
      res.on('data', function (chunk) {
        body = body + chunk;
      });
      res.on('end',function(){
        
      });
    });
    req.write(JSON.stringify({
      'type' : 'geofence_client',
      'is_at_home' : false,
      'name' : 'jeedom_'+((Math.random() + 1).toString(36).substring(7))
    }));
    req.end();
 }, 30000)

}
