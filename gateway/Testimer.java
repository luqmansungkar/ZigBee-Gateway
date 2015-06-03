import java.util.TimerTask;
import java.util.Timer;

public class Testimer{
	
	public static void main(String[] args){
		// And From your main() method or any other method
		Timer timer = new Timer();
		timer.schedule(new SayHello(), 0, 5000);
	}

	static class SayHello extends TimerTask {
    public void run() {
       System.out.println("Hello World!"); 
    }
 }
}