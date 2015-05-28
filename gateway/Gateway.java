/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package aaa;
import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
//import org.apache.commons.lang.NumberUtils;
import org.eclipse.paho.client.mqttv3.IMqttDeliveryToken;
import org.eclipse.paho.client.mqttv3.MqttCallback;
import org.eclipse.paho.client.mqttv3.MqttClient;
import org.eclipse.paho.client.mqttv3.MqttException;
import org.eclipse.paho.client.mqttv3.MqttMessage;

/**
 *
 * @author Fauziah Rahmawati
 */
public class Gateway implements MqttCallback{
    
    public static MqttClient clientPub;
    public static MqttClient clientSub;
    public static MqttMessage message;
    public static Thread mqttThread;

    public Gateway(){
        try {
            // membuat client sebagai publisher
            clientPub = new MqttClient("tcp://128.199.236.53:1883", "Publisher");
            System.out.println("Publisher created");
            // membuat client sebagai subscriber
            clientSub = new MqttClient("tcp://128.199.236.53:1883", "Subscriber");
            System.out.println("Subscriber created");
            connectMosquitto(clientPub);
            connectMosquitto(clientSub);
        } catch (MqttException ex) {
            ex.printStackTrace();
        }
    }
    
    /**
     * Method untuk menghubungkan client dengan broker
     * @param c merupakan client yang akan dihubungkan ke broker
     */
    private void connectMosquitto(final MqttClient c){
        try {
            // menghubungkan client dengan broker
            c.connect();
            // jika client adalah subscriber diatur agar subscribe ke topik tentang jaringan ZigBee
            if(c.getClientId().equalsIgnoreCase("Subscriber")){
                // client diatur agar bisa menerima pesan dari broker
                /*c.setCallback(Gateway.this);
                // subscribe info tentang coordinator
                c.subscribe("coordinator/#");
                // subscribe info tentang lampu
                c.subscribe("lights/#");
                // subscribe info tentang grup dan scene
                c.subscribe("groups/#");*/
                c.subscribe("zigbee/lights");
                System.out.println("Subscriber connected");
            }
            else{
                System.out.println("Publisher connected");
            }
        }
        catch (MqttException ex) {
            ex.printStackTrace();
        }
    }
    
    /**
     * Method untuk mengirimkan pesan ke broker
     * @param topic merupakan nama topik dari pesan yang akan dikirim
     * @param msg merupakan isi pesan yang akan dikirim
     */
    private void publish(String topic, String msg){
        try {
            // membuat sebuah pesan MQTT
            message = new MqttMessage();
            // mengatur payload dari message menjadi array of byte dari pesan yang dikirim oleh ZigBee coordinator
            message.setPayload(msg.getBytes());
            // mem-publish pesan ke MQTT broker
            clientPub.publish(topic, message);
            System.out.println("Client publish: " + topic);
        }
        catch (MqttException ex) {
            ex.printStackTrace();
        }
    }

    /**
     * Method yang dipanggil jika koneksi terputus
     * @param thrwbl alasan yang menyebabkan koneksi terputus
     */
    @Override
    public void connectionLost(Throwable thrwbl) {
        System.out.println("Connection lost");
        System.out.println(thrwbl);
    }

    /**
     * Method yang dipanggil jika ada pesan dari broker
     * @param topic merupakan nama topik dari pesan yang dikirim oleh broker
     * @param msg merupakan isi pesan yang dikirim oleh broker
     */
    @Override
    public void messageArrived(String topic, MqttMessage msg){
        System.out.println("Topic : " + topic + ", message : " + msg);
        String command = msg.toString();
        // mengecek isi pesan dari broker
        checkCommand(topic, command);
    }

    @Override
    public void deliveryComplete(IMqttDeliveryToken imdt) {
        System.out.println("Delivery complete");
    }
    
    /**
     * Method yang digunakan untuk menjalankan perintah dari broker
     * @param topic merupakan nama topik dari pesan yang dikirim broker
     * @param command merupakan isi pesan dari broker
     */
    void checkCommand(String topic, String command){
        String msg = "";
        //String wrong = "Wrong command";
        //String[] topicArr = topic.split("/");
        //int length = topicArr.length;
                
        // menjalankan perintah untuk koordinator
        /*if(topic.equalsIgnoreCase("coordinator")){
            // mengijinkan perangkat tergabung dalam jaringan
            if(command.equalsIgnoreCase("permitjoin")){
                msg = executeREST("POST", "http://localhost:8080/coordinator/permitjoin", null);
            }
            // mereset coordinator
            else if(command.equalsIgnoreCase("reset")){
                msg = executeREST("POST", "http://localhost:8080/coordinator/reset", null);
            }
            // mencari perangkat ZigBee di sekitar coordinator
            else if(command.equalsIgnoreCase("scan")){
                msg = executeREST("POST", "http://localhost:8080/coordinator/scan", null);
            }
            // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
            else{
                msg = wrong;
            }
        }*/
        /**********************************************************************/
        // menjalankan perintah tentang lampu
        /*else if(topic.startsWith("lights")){
            // mengecek perintah untuk salah satu lampu
            if(length > 1){
                if(NumberUtils.isDigits(topicArr[1])){
                    int lightId = Integer.parseInt(topicArr[1]);
                    Light light = Lights.getLightById(lightId);
                    if(light != null){
                        // menampilkan informasi tentang salah satu lampu
                        if(command.equalsIgnoreCase("show")){
                            msg = executeREST("GET", "http://localhost:8080/lights/" + lightId, null);
                        }
                        // menghidupkan lampu
                        else if(command.equalsIgnoreCase("on")){
                            msg = executeREST("PUT", "http://localhost:8080/lights/" + lightId, "/state?on=true");
                        }
                        // mematikan lampu
                        else if(command.equalsIgnoreCase("off")){
                            msg = executeREST("PUT", "http://localhost:8080/lights/" + lightId, "/state?on=false");
                        }
                        else if(command.contains("=")){
                            String[] commandArr = command.split("=");
                            // mengubah nama lampu
                            if(commandArr[0].equalsIgnoreCase("name")){
                                msg = executeREST("PUT", "http://localhost:8080/lights/" + lightId, "?name=" + commandArr[1]);
                            }
                            // mengubah brightness lampu
                            else if(commandArr[0].equalsIgnoreCase("b")){
                                if(NumberUtils.isDigits(commandArr[1])){
                                    msg = executeREST("PUT", "http://localhost:8080/lights/" + lightId, "/state?brightness=" + commandArr[1]);
                                }
                                else{
                                    msg = "Value must a digit";
                                }
                            }
                            // mengubah hue lampu
                            else if(commandArr[0].equalsIgnoreCase("h")){
                                if(NumberUtils.isDigits(commandArr[1])){
                                    msg = executeREST("PUT", "http://localhost:8080/lights/" + lightId, "/state?hue=" + commandArr[1]);
                                }
                                else{
                                    msg = "Value must a digit";
                                }
                            }
                            // mengubah saturation lampu
                            else if(commandArr[0].equalsIgnoreCase("s")){
                                if(NumberUtils.isDigits(commandArr[1])){
                                    msg = executeREST("PUT", "http://localhost:8080/lights/" + lightId, "/state?saturation=" + commandArr[1]);
                                }
                                else{
                                    msg = "Value must a digit";
                                }
                            }
                            // melakukan identify terhadap sebuah lampu
                            else if(commandArr[0].equalsIgnoreCase("identify")){
                                if(NumberUtils.isDigits(commandArr[1])){
                                    msg = executeREST("POST", "http://localhost:8080/lights/" + lightId, "/identify?time=" + commandArr[1]);
                                }
                                else{
                                    msg = "Value must a digit";
                                }
                            }
                        }
                        // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                        else{
                            msg = wrong;
                        }
                    }
                    // jika id lampu salah akan memberikan notifikasi
                    else{
                        msg = "Wrong light ID";
                    }
                }
                else{
                    msg = "Wrong light ID";
                }
            }
            // menampilkan daftar semua lampu
            else if(command.equalsIgnoreCase("show")){
                msg = executeREST("GET", "http://localhost:8080/lights", null);
            }
            // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
            else{
                msg = wrong;
            }                
        }*/
        /**********************************************************************/
        // menjalankan perintah tentang grup
        /*else if(topic.startsWith("groups")){
            // menjalankan perintah untuk salah satu grup
            if(length > 1){
                if(NumberUtils.isDigits(topicArr[1])){
                    int groupId = Integer.parseInt(topicArr[1]);
                    Group group = Groups.getGroupById(groupId);
                    if(group != null){

                        // menjalankan perintah tentang scene
                        if(length > 2){
                            if(topicArr[2].equalsIgnoreCase("scenes")){
                                // menjalankan perintah untuk salah satu scene
                                if(length > 3){
                                    if(NumberUtils.isDigits(topicArr[3])){
                                        int sceneId = Integer.parseInt(topicArr[3]);
                                        Scene scene = group.getScenes().getSceneById(sceneId);
                                        if(scene != null){
                                            // menampilkan informasi tentang salah satu scene
                                            if(command.equalsIgnoreCase("show")){
                                                msg = executeREST("GET", "http://localhost:8080/groups/" + groupId + "/scenes/" + sceneId, null);
                                            }
                                            // menyimpan keadaan lampu saat ini ke dalam scene
                                            else if(command.equalsIgnoreCase("store")){
                                                msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId + "/scenes/" + sceneId, "/store");
                                            }
                                            // memanggil scene yang sudah tersimpan
                                            else if(command.equalsIgnoreCase("recall")){
                                                msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId + "/scenes/" + sceneId, "/recall");
                                            }
                                            // menghapus salah satu scene
                                            else if(command.equalsIgnoreCase("delete")){
                                                msg = executeREST("DELETE", "http://localhost:8080/groups/" + groupId + "/scenes/" + sceneId, null);
                                            }
                                            else if(command.contains("=")){
                                                String[] commandArr = command.split("=");
                                                // mengubah nama scene
                                                if(commandArr[0].equalsIgnoreCase("name")){
                                                    msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId + "/scenes/" + sceneId, "?name=" + commandArr[1]);
                                                }
                                                // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                                                else{
                                                    msg = wrong;
                                                }
                                            }
                                            // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                                            else{
                                                msg = wrong;
                                            }
                                        }
                                        // jika id scene salah akan memberikan notifikasi ke broker
                                        else{
                                            msg = "Wrong scene ID";
                                        }
                                    }
                                    else{
                                        msg = "Wrong scene ID";
                                    }
                                }
                                // membuat scene baru
                                else if(command.equalsIgnoreCase("create")){
                                    msg = executeREST("POST", "http://localhost:8080/groups/" + groupId + "/scenes", null);
                                }
                                // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                                else{
                                    msg = wrong;
                                }
                            }
                            // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                            else{
                                msg = wrong;
                            }
                        }
                        // menjalankan perintah tentang salah saatu grup
                        else{
                            // menampilkan informasi tentang sebuah grup
                            if(command.equalsIgnoreCase("show")){
                                msg = executeREST("GET", "http://localhost:8080/groups/" + groupId, null);
                            }
                            // menghidupkan semua lampu yang tergabung dalam grup
                            else if(command.equalsIgnoreCase("on")){
                                msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "/state?on=true");
                            }
                            // mematikan semua lampu yang tergabung dalam grup
                            else if(command.equalsIgnoreCase("off")){
                                msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "/state?on=false");
                            }
                            // menghapus sebuah grup
                            else if(command.equalsIgnoreCase("delete")){
                                msg = executeREST("DELETE", "http://localhost:8080/groups/" + groupId, null);
                            }
                            else if(command.contains("=")){
                                String[] commandArr = command.split("=");
                                // mengubah nama grup
                                if(commandArr[0].equalsIgnoreCase("name")){
                                    msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "?name=" + commandArr[1]);
                                }
                                // menambahkan lampu ke dalam grup
                                else if(commandArr[0].equalsIgnoreCase("lights")){
                                    if(NumberUtils.isDigits(commandArr[1])){
                                        Light light = Lights.getLightById(Integer.parseInt(commandArr[1]));
                                        if(light != null){
                                            msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "?lights=" + commandArr[1]);
                                        }
                                        else{
                                            msg = "Wrong light ID";
                                        }
                                    }
                                    else{
                                        msg = "Value must a digit";
                                    }
                                }
                                // mengubah brightness dari semua lampu yang tergabung dalam grup
                                else if(commandArr[0].equalsIgnoreCase("b")){
                                    if(NumberUtils.isDigits(commandArr[1])){
                                        msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "/state?brightness=" + commandArr[1]);
                                    }
                                    else{
                                        msg = "Value must a digit";
                                    }
                                }
                                // mengubah hue dari semua lampu yang tergabung dalam grup
                                else if(commandArr[0].equalsIgnoreCase("h")){
                                    if(NumberUtils.isDigits(commandArr[1])){
                                        msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "/state?hue=" + commandArr[1]);
                                    }
                                    else{
                                        msg = "Value must a digit";
                                    }
                                }
                                // mengubah saturation dari semua lampu yang tergabung dalam grup
                                else if(commandArr[0].equalsIgnoreCase("s")){
                                    if(NumberUtils.isDigits(commandArr[1])){
                                        msg = executeREST("PUT", "http://localhost:8080/groups/" + groupId, "/state?saturation=" + commandArr[1]);
                                    }
                                    else{
                                        msg = "Value must a digit";
                                    }
                                }
                                // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                                else{
                                    msg = wrong;
                                }
                            }
                            // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                            else{
                                msg = wrong;
                            }
                        }
                    }
                    // jika id grup yang dimasukkan salah akan mengirim notifikasi ke broker
                    else{
                        msg = "Wrong group ID";
                    }
                }
                else{
                    msg = "Wrong group ID";
                }
            }
            else{
                // menampilkan daftar semua grup dalam jaringan
                if(command.equalsIgnoreCase("show")){
                    msg = executeREST("GET", "http://localhost:8080/groups", null);
                }
                // membuat sebuah grup
                else if(command.equalsIgnoreCase("create")){
                    msg = executeREST("POST", "http://localhost:8080/groups", null);
                }
                // jika perintah yang dikirim salah memberikan notifikasi bahwa perintah salah
                else{
                    msg = wrong;
                }
            }
        }*/

        if (command.equalsIgnoreCase("nyala")) {
            msg = executeREST("POST", "192.168.137.68:8080/api/4171700133/lights/1", "/state?on=true");
        }else{
            msg = executeREST("POST", "192.168.137.68:8080/api/4171700133/lights/1", "/state?on=false");
        }
        // mengirimkan pesan dan topik ke broker
        publish("zigbee/lights", msg);
    }
    
    /**
     * Method untuk mengirimkan perintah REST ke REST API
     * @param method merupakan jenis method yang digunakan
     * @param targetURL merupakan alamat URI dari server REST
     * @param parameter merupakan parameter dari perintah REST
     * @return mengembalikan respon dari REST dalam bentuk String
     */
    private String executeREST(String method, String targetURL, String parameter){
        URL url;
        HttpURLConnection connection = null;
        try {
            // membuat alamat URI yang dituju
            if(parameter != null){
                url = new URL(targetURL + parameter);
            }
            else{
                url = new URL(targetURL);
            }
            // membuat koneksi ke URL
            connection = (HttpURLConnection)url.openConnection();
            // mengatur method sesuai permintaan dari gateway
            connection.setRequestMethod(method);
            connection.setUseCaches(false);
            connection.setDoInput(true);
            connection.setDoOutput(true);
            connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            connection.setRequestProperty("Content-Language", "en-US");
            
            // menuliskan isi perintah
            if(parameter != null){
                // mengatur parameter sebagai request property
                connection.setRequestProperty("Content-Length", "" + Integer.toString(parameter.getBytes().length));
                DataOutputStream wr = new DataOutputStream (connection.getOutputStream());
                // mengirim request
                wr.write(parameter.getBytes());
                wr.flush ();
                wr.close ();
            }
            
            // menghubungkan ke server
            connection.connect();
            // membaca respon
            InputStream is = connection.getInputStream();
            BufferedReader rd = new BufferedReader(new InputStreamReader(is));
            String line;
            StringBuffer response = new StringBuffer();
            // membaca respon perbaris
            while((line = rd.readLine()) != null){
                response.append(line);
                response.append('\r');
            }
            rd.close();
            // jika koneksi publisher terputus, akan melakukan koneksi ulang
            if(!clientPub.isConnected()){
                connectMosquitto(clientPub);
            }
            // jika koneksi subscriber terputus, akan melakukan koneksi ulang
            if(!clientSub.isConnected()){
                connectMosquitto(clientSub);
            }
            String resp = response.toString();
            // mengirimkan respon ke pemanggil
            return resp;
        }
        catch(Exception e){
                e.printStackTrace();
                return null;
        }
        finally{
            if(connection != null){
                connection.disconnect(); 
            }
        }
    }
}
