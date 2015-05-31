import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

public class Tesdb{
	public static void main(String[] args){
		Connection con = null;
		Statement st = null;
		ResultSet rs = null;

		String url = "jdbc:mysql://localhost:3306/gateway";
		String user = "root";
		String password = "root";

		try{
			con = DriverManager.getConnection(url, user, password);
			st = con.createStatement();
			rs = st.executeQuery("Select * from things");
			if (rs.next()) {
				System.out.println(rs.getString(1));
			}
		}catch(Exception e){
			System.out.println("eror");
		}finally{
			try{
				if (rs != null) {
					rs.close();
				}
				if (st != null) {
					st.close();
				}
				if (con != null) {
					con.close();
				}
			}catch(Exception e){
				System.out.println("error di finally");
			}
		}
	}
}